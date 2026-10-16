<?php


/**
 * @author Jonathan Rabie
 */

namespace Tina4Jobs\Tina4Queue;

use Job;
use Psr\Cache\InvalidArgumentException;
use Tina4\Data;
use Tina4\Utilities;

/**
 * Database backed queue, stored in tina4_jobs / tina4_failed_jobs.
 *
 * Queue reads go straight to $DBA rather than through ORM::load(): load() caches
 * its results unless TINA4_CACHE_ON is false, so two polls in the same second
 * could both be handed the same job.
 */
class Tina4DatabaseJob extends Data implements Tina4QueueInterface
{
    private const JOBS_TABLE = "tina4_jobs";

    /**
     * How many candidates to try per poll when another worker claims them first
     */
    private const CLAIM_ATTEMPTS = 5;

    /**
     * @inheritDoc
     */
    public function addFutureJob(object|string $job, int $availableAt = 0, string $queue = 'default'): void
    {
        try {
            if ($availableAt <= 0) {
                $availableAt = time();
            }

            $attempt = 1;

            if (is_object($job) && method_exists($job, 'getAttempt')) {
                $attempt = $job->getAttempt();
            }

            $newJob = new Job();
            $newJob->queue = $queue;
            $newJob->payload = Tina4JobPayload::encode($job);
            $newJob->attempts = $attempt;
            $newJob->reservedAt = -1;
            $newJob->availableAt = $availableAt;
            $newJob->createdAt = time();

            if (!$newJob->save()) {
                Tina4JobLog::error("Failed to add job to queue {$queue}: the insert into " . self::JOBS_TABLE . " failed");
            }
        } catch (\Throwable $exception) {
            Tina4JobLog::error("Failed to add job to queue {$queue}: " . $exception->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function addJob(object|string $job, string $queue = "default"): void
    {
        $availableAt = time();

        // A job pushed again after a failed attempt waits out its retry delay
        if (is_object($job) && method_exists($job, 'getAttempt') && $job->getAttempt() > 1) {
            $availableAt += $job->getTimeBetweenAttempts();
        }

        $this->addFutureJob($job, $availableAt, $queue);
    }

    /**
     * Claims the next available job on the queue. The claim is a conditional update
     * (reserved_at < 0), so when several workers poll the same queue only one of them
     * gets each job.
     * @inheritDoc
     */
    public function getNextJob(string $queue = "default"): ?object
    {
        $now = time();
        $sql = "select id, payload from " . self::JOBS_TABLE . "
                 where queue = " . $this->quote($queue) . "
                   and reserved_at < 0
                   and available_at <= {$now}
                 order by available_at, id";

        $result = $this->DBA->fetch($sql, self::CLAIM_ATTEMPTS);
        $candidates = $result ? $result->asArray() : [];

        foreach ($candidates as $candidate) {
            $jobId = (int)$candidate["id"];

            if (!$this->claim($jobId, $now)) {
                continue; // another worker got there first
            }

            $job = Tina4JobPayload::decode($candidate["payload"]);

            if ($job === null) {
                $this->markJobFailed(
                    "Job payload could not be unserialized - the job class is missing or was renamed",
                    $jobId,
                    $candidate["payload"],
                    $queue
                );
                continue;
            }

            /*
             * Set the job ID on the job object
             * This jobid is used to mark the job as completed or failed
             */
            $job->setJobId($jobId);

            return $job;
        }

        return null;
    }

    /**
     * Returns jobs whose worker died mid-run to the queue, or fails them when they
     * have no attempts left. A job is stale once it has been reserved for longer than
     * $staleAfter seconds, which must be longer than the longest job timeout.
     * @param string $queue The queue to recover
     * @param int $staleAfter Seconds a job may stay reserved before it is considered abandoned
     * @param array $runningJobIds Job ids this worker is still running, never recovered
     * @return int Number of jobs recovered
     */
    public function recoverStaleJobs(string $queue, int $staleAfter, array $runningJobIds = []): int
    {
        $cutoff = time() - $staleAfter;
        $sql = "select id, payload from " . self::JOBS_TABLE . "
                 where queue = " . $this->quote($queue) . "
                   and reserved_at > 0
                   and reserved_at < {$cutoff}
                 order by id";

        $result = $this->DBA->fetch($sql, 100);
        $staleJobs = $result ? $result->asArray() : [];
        $recovered = 0;

        foreach ($staleJobs as $staleJob) {
            $jobId = (int)$staleJob["id"];

            if (in_array($jobId, $runningJobIds, true)) {
                continue;
            }

            $job = Tina4JobPayload::decode($staleJob["payload"]);
            $reason = "Job was reserved for over {$staleAfter}s without finishing - the worker running it was probably killed";

            if ($job !== null && Tina4JobPayload::canRetry($job) && $job->attempt()) {
                $job->setJobId($jobId);
                $this->releaseJob($job, $job->getTimeBetweenAttempts(), $queue);
                Tina4JobLog::info("Recovered stale job {$jobId} (" . get_class($job) . "), attempt {$job->getAttempt()}", $queue);
            } else {
                $this->markJobFailed($reason, $jobId, $staleJob["payload"], $queue);
                Tina4JobLog::error("Stale job {$jobId} has no attempts left and was marked failed", $queue);
            }

            $recovered++;
        }

        return $recovered;
    }

    /**
     * This function will mark the job as completed and remove it from the jobs table
     * @param int $jobId
     * @return void
     * @throws \Exception
     */
    public function markJobCompleted(int $jobId): void
    {
        $this->DBA->exec("delete from " . self::JOBS_TABLE . " where id = ?", $jobId);
        $this->DBA->commit();
    }

    /**
     * This function will add the failed job to the failed jobs table
     * and remove the job from the jobs table
     * @param string $exception The exception message
     * @param int|string $jobId The job id as stored in the jobs table
     * @param string $payload The payload of the job that failed, default is "No Payload"
     * @param string $queue The queue the job was in, default is "default"
     * @return void
     * @throws InvalidArgumentException
     */
    public function markJobFailed(
        string $exception, int|string $jobId, string $payload = "No Payload", string $queue = "default"
    ): void
    {
        $failedJob = new \FailedJob();
        $failedJob->uuid = (new Utilities())->getGUID();
        $failedJob->connection = self::class;
        $failedJob->queue = $queue;
        $failedJob->payload = $payload;
        $failedJob->exception = Tina4JobPayload::truncate($exception);
        $failedJob->failedAt = date("Y-m-d H:i:s");

        if ($failedJob->save()) {
            if ((int)$jobId > 0) {
                $this->markJobCompleted((int)$jobId);
            }
        } else {
            Tina4JobLog::error("Failed to record failed job {$jobId} in tina4_failed_jobs", $queue);
        }
    }


    /**
     * This function will release the job back to the queue
     * @param object|string $job The job to release
     * @param int $timeBetween The time between attempts
     * @param string $queue The queue to release the job to, default is "default"
     * @return void
     */
    public function releaseJob(object|string $job, int $timeBetween, string $queue="default"): void
    {
        $this->DBA->exec(
            "update " . self::JOBS_TABLE . "
                set queue = ?, payload = ?, attempts = ?, available_at = ?, reserved_at = -1
              where id = ?",
            $queue,
            Tina4JobPayload::encode($job),
            $job->getAttempt(),
            time() + $timeBetween,
            (int)$job->getJobId()
        );
        $this->DBA->commit();
    }

    /**
     * @inheritDoc
     */
    public function requeueFailedJob(string $uuid): void
    {
        if($failedJob = (new \FailedJob())->load("uuid = ?", [$uuid])) {
            if($failedJob->payload != 'No Payload') {
                if ($jobPayload = Tina4JobPayload::decode($failedJob->payload)) {
                    if (method_exists($jobPayload, 'resetAttempts')) {
                        $jobPayload->resetAttempts();
                    }

                    $this->addJob($jobPayload, $failedJob->queue);
                    $failedJob->delete();
                }
            }
        }
    }

    /**
     * Reserve a job, succeeding only if no other worker reserved it first
     * @param int $jobId
     * @param int $now
     * @return bool
     */
    private function claim(int $jobId, int $now): bool
    {
        $this->DBA->exec(
            "update " . self::JOBS_TABLE . " set reserved_at = ? where id = ? and reserved_at < 0",
            $now,
            $jobId
        );

        $changed = $this->changedRows();
        $this->DBA->commit();

        return $changed === null || $changed > 0;
    }

    /**
     * Rows changed by the last statement on this connection, or null when the
     * database type gives no portable way to ask
     * @return int|null
     */
    private function changedRows(): ?int
    {
        $sql = match (true) {
            str_contains(get_class($this->DBA), "MySQL") => "select row_count() as changed",
            str_contains(get_class($this->DBA), "SQLite3") => "select changes() as changed",
            default => null,
        };

        if ($sql === null) {
            return null;
        }

        $result = $this->DBA->fetch($sql, 1);
        $rows = $result ? $result->asArray() : [];

        return isset($rows[0]["changed"]) ? (int)$rows[0]["changed"] : null;
    }

    /**
     * Quote a string literal for the queue queries
     * @param string $value
     * @return string
     */
    private function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}

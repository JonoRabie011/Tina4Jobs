<?php

/**
 * Redis-based Job Queue Management
 * This class allows you to add jobs to a Redis queue and manage Redis connections.
 *
 * Keys, with the default prefix:
 *   tina4jobs:queue:{queue}     list of jobs ready to run
 *   tina4jobs:delayed:{queue}   sorted set of future jobs, scored by the time they become available
 *
 * Failed jobs are recorded in the database (tina4_failed_jobs), like the database driver.
 *
 * Limitation: a job is removed from Redis when a worker takes it, so a worker killed
 * mid-job loses that job. Use the database driver where that matters.
 *
 * @package Tina4Jobs
 * @version 1.1.0
 * @author Jonathan Rabie
 */

namespace Tina4Jobs\Tina4Queue;

use Redis;
use RedisException;
use Tina4\Utilities;

class Tina4RedisJob implements Tina4QueueInterface
{

    /**
     * The redis connection to the jobs queue
     * @var Redis
     */
    private Redis $jobsConnection;

    /**
     * Prefix for every key this driver writes, so queues never collide with other data in the same Redis
     */
    private string $prefix;


    public function __construct()
    {
        $this->jobsConnection = new Redis();
        $this->prefix = $_ENV["TINA4_JOBS_REDIS_PREFIX"] ?? "tina4jobs:";

        $this->openConnection();
    }

    /**
     * Open a connection to the Redis server, using REDIS_HOST, REDIS_PORT and the optional
     * REDIS_PASSWORD / REDIS_DATABASE
     */
    private function openConnection(): void
    {
        try {
            $this->jobsConnection->connect($_ENV["REDIS_HOST"] ?? "localhost", (int)($_ENV["REDIS_PORT"] ?? 6379), 5.0);

            if (!empty($_ENV["REDIS_PASSWORD"])) {
                $this->jobsConnection->auth($_ENV["REDIS_PASSWORD"]);
            }

            if (!empty($_ENV["REDIS_DATABASE"])) {
                $this->jobsConnection->select((int)$_ENV["REDIS_DATABASE"]);
            }
        } catch (RedisException $e) {
            Tina4JobLog::error("Failed to connect to Redis: " . $e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function addJob(object|string $job, string $queue = "default"): void
    {
        try {
            $this->jobsConnection->lPush($this->queueKey($queue), Tina4JobPayload::encode($job));
        } catch (RedisException $e) {
            Tina4JobLog::error("Failed to add job to queue: " . $e->getMessage(), $queue);
        }
    }

    /**
     * @inheritDoc
     */
    public function addFutureJob(object|string $job, int $availableAt = 0, string $queue = 'default'): void
    {
        if ($availableAt <= time()) {
            $this->addJob($job, $queue);
            return;
        }

        try {
            $this->jobsConnection->zAdd($this->delayedKey($queue), $availableAt, Tina4JobPayload::encode($job));
        } catch (RedisException $e) {
            Tina4JobLog::error("Failed to add delayed job to queue: " . $e->getMessage(), $queue);
        }
    }

    /**
     * @inheritDoc
     */
    public function getNextJob(string $queue = "default"): ?object
    {
        $this->moveDueJobs($queue);

        $payload = $this->jobsConnection->rPop($this->queueKey($queue));

        if (empty($payload)) {
            return null;
        }

        $job = Tina4JobPayload::decode($payload);

        if ($job === null) {
            $this->markJobFailed(
                "Job payload could not be unserialized - the job class is missing or was renamed",
                (new Utilities())->getGUID(),
                $payload,
                $queue
            );

            return null;
        }

        /*
         * Set the job ID on the job object as a GUID since Redis does not have an auto-incrementing ID like a database.
         * This allows us to track the job for completion or failure.
         */
        $job->setJobId((new Utilities())->getGUID());

        return $job;
    }

    /**
     * Move delayed jobs that are now due onto the ready list. zRem decides which worker
     * moves each job, so a job is never pushed twice when several workers poll.
     * @param string $queue
     * @return void
     */
    private function moveDueJobs(string $queue): void
    {
        $delayedKey = $this->delayedKey($queue);
        $dueJobs = $this->jobsConnection->zRangeByScore($delayedKey, "-inf", (string)time(), ["limit" => [0, 100]]);

        foreach ($dueJobs ?: [] as $payload) {
            if ($this->jobsConnection->zRem($delayedKey, $payload) > 0) {
                $this->jobsConnection->lPush($this->queueKey($queue), $payload);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function markJobCompleted(int $jobId): void
    {
        // Nothing to do: the job left Redis when it was taken off the queue
    }


    /**
     * @inheritDoc
     */
    public function markJobFailed(string $exception, string|int $jobId = "", string $payload = "No Payload", string $queue = "default"): void
    {
        try {
            $failedJob = new \FailedJob();
            $failedJob->uuid = !empty($jobId) ? (string)$jobId : (new Utilities())->getGUID();
            $failedJob->connection = self::class;
            $failedJob->queue = $queue;
            $failedJob->payload = $payload;
            $failedJob->exception = Tina4JobPayload::truncate($exception);
            $failedJob->failedAt = date("Y-m-d H:i:s");

            if (!$failedJob->save()) {
                Tina4JobLog::error("Failed to record failed job {$jobId} in tina4_failed_jobs", $queue);
            }
        } catch (\Throwable $e) {
            Tina4JobLog::error("Failed to mark job as failed: " . $e->getMessage(), $queue);
        }
    }


    /**
     * Put a job back on the queue to run again after $timeBetween seconds. Does not block:
     * the job waits in the delayed set rather than in a sleeping worker.
     * @inheritDoc
     */
    public function releaseJob(object|string $job, int $timeBetween, string $queue = "default"): void
    {
        $this->addFutureJob($job, time() + $timeBetween, $queue);
    }

    /**
     * Close the Redis connection when the instance is destroyed.
     */
    public function __destruct()
    {
        try {
            if ($this->jobsConnection->isConnected()) {
                $this->jobsConnection->close();
            }
        } catch (RedisException) {
            // Already gone
        }
    }

    /**
     * @inheritDoc
     */
    public function requeueFailedJob(string $uuid): void
    {
        if ($failedJob = (new \FailedJob())->load("uuid = ?", [$uuid])) {
            if ($failedJob->payload != 'No Payload' && ($job = Tina4JobPayload::decode($failedJob->payload))) {
                if (method_exists($job, 'resetAttempts')) {
                    $job->resetAttempts();
                }

                $this->addJob($job, $failedJob->queue);
                $failedJob->delete();
            }
        }
    }

    private function queueKey(string $queue): string
    {
        return $this->prefix . "queue:" . $queue;
    }

    private function delayedKey(string $queue): string
    {
        return $this->prefix . "delayed:" . $queue;
    }
}

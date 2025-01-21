<?php


/**
 * @author Jonathan Rabie
 */

namespace Tina4Jobs\Tina4Queue;

use Job;
use Psr\Cache\InvalidArgumentException;
use Tina4\Data;
use Tina4\Utilities;

class Tina4DatabaseJob extends Data implements Tina4QueueInterface
{

    public function addJob(object|string $job, string $queue = "default"): void
    {
        try {
            $availableAt = time();
            $attempt = 1;

            if(property_exists($job, 'attempt')) {
                $attempt = $job->getAttempt();

                if($attempt > 1) {
                    $availableAt = time() + $job->getTimeBetweenAttempts();
                }
            }

            $newJob = new Job();
            $newJob->queue = $queue;
            $newJob->payload = convert_uuencode(serialize($job));
            $newJob->attempts = $attempt;
            $newJob->reservedAt = -1;
            $newJob->availableAt = $availableAt;
            $newJob->createdAt = time();
            $newJob->save();
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public function getNextJob(string $queue = "default"): ?object
    {
        $job = new Job();
        if($currentJob = $job->load("reserved_at < 0 and id > 0 and queue = ? and available_at < ?", [$queue, time()])) {
            $currentJobUnSerialized = unserialize(convert_uudecode($currentJob->payload));

            $currentJob->reservedAt = time();
            $currentJob->save();
            /*
             * Set the job ID on the job object
             * This jobid is used to mark the job as completed or failed
             */
            $currentJobUnSerialized->setJobId($currentJob->id);
            return $currentJobUnSerialized;
        }

        return null;
    }


    /**
     * This function will mark the job as completed and remove it from the jobs table
     * @param int $jobId
     * @return void
     * @throws \Exception
     */
    public function markJobCompleted(int $jobId): void
    {
        $job = new Job();
        $job->id = $jobId;
        $job->delete();
    }

    /**
     * This function will add the failed job to the failed jobs table
     * and remove the job from the jobs table
     * @param string $exception The exception message
     * @param int $jobId The job id as stored in the jobs table
     * @return void
     * @throws InvalidArgumentException
     */
    public function markJobFailed(string $exception, int $jobId): void
    {
        $failedJob = new \FailedJob();
        $failedJob->uuid = (new Utilities())->getGUID();
        $failedJob->connection = Tina4RedisJob::class;
        $failedJob->queue = "default";
        $failedJob->payload = 'Add payload here';
        $failedJob->exception = $exception;
        $failedJob->failedAt = date("Y-m-d H:i:s");

        if ($failedJob->save()) {
            $job = new Job();
            $job->id = $jobId;
            $job->delete();
        }
    }


    /**
     * This function will release the job back to the queue
     * @param object|string $job The job to release
     * @param int $timeBetween The time between attempts
     * @return void
     */
    public function releaseJob(object|string $job, int $timeBetween): void
    {
        if($savedJob = (new Job())->load("id = ?", [$job->getJobId()])) {
            $savedJob->payload = convert_uuencode(serialize($job));
            $savedJob->attempts = $job->getAttempt();
            $savedJob->availableAt = time() + $timeBetween;
            $savedJob->reservedAt = -1;
            $savedJob->save();
        }
    }
}
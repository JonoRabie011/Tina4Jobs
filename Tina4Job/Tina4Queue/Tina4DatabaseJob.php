<?php

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

            if(property_exists($job, 'attempts') && $job->attempts > 0) {
                $availableAt = time() + $job->timeBetweenAttempts;
            }

            $newJob = new Job();
            $newJob->queue = $queue;
            $newJob->payload = convert_uuencode(serialize($job));
            $newJob->attempts = $job->attempts;
            $newJob->reservedAt = null;
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
        if($currentJob = $job->load("id > 0 and queue = ? and availableAt < ?", [$queue, time()])) {
            $currentJobUnSerialized = unserialize(convert_uudecode($currentJob->payload));
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
     * @param string $exception
     * @param int $jobId
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
        $failedJob->failed_at = date("Y-m-d H:i:s");

        if ($failedJob->save()) {
            $job = new Job();
            $job->id = $jobId;
            $job->delete();
        }
    }
}
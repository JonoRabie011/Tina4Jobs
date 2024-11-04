<?php

namespace Tina4Jobs\Tina4Queue;

use Job;
use Tina4\Data;
use Tina4\Utilities;

class Tina4DatabaseJob extends Data implements Tina4QueueInterface
{

    public function addJob(object|string $job, string $queue = "default"): void
    {
        try {
            $newJob = new Job();
            $newJob->queue = $queue;
            $newJob->payload = convert_uuencode(serialize($job));
            $newJob->attempts = 0;
            $newJob->reservedAt = null;
            $newJob->availableAt = time();
            $newJob->createdAt = time();
            $newJob->save();
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }
    }

    public function getNextJob(string $queue = "default"): ?object
    {
        $job = new Job();
        if($currentJob = $job->load("id > 0")) {
            $currentJobUnSerialized = unserialize(convert_uudecode($currentJob->payload));
            /*
             * Set the job ID on the job object
             * This jobid is used to mark the job as completed or failed
             */
            $currentJobUnSerialized->jobId = $currentJob->id;
            return $currentJobUnSerialized;
        }

        return null;
    }


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
     * @throws \Psr\Cache\InvalidArgumentException
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
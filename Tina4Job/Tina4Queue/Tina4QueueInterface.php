<?php

namespace Tina4Jobs\Tina4Queue;

interface Tina4QueueInterface
{

    /**
     * This function handles delayed jobs
     * @param object|string $job The job to add to the queue
     * @param int $availableAt The tome the job should be processed
     * @param string $queue The queue to add the job to
     * @return void
     */
    public function addFutureJob(object|string $job, int $availableAt = 0, string $queue = 'default'): void;

    /**
     * Add new job to the queue
     * @param object|string $job The job to add to the queue
     * @param string $queue The queue to add the job to
     * @return void
     */
    public function addJob(object|string $job, string $queue = "default"): void;

    /**
     * Get the next job from the queue.
     * @param string $queue The queue to get the job from
     * @return object|null The next job from the queue
     */
    public function getNextJob(string $queue = "default"): ?object;

    /**
     * Mark a job as completed
     * @param int $jobId
     * @return void
     */
    public function markJobCompleted(int $jobId): void;

    /**
     * Mark a job as failed
     * @param string $exception The exception message
     * @param int | string $jobId The job ID
     * @param string $payload The job payload
     * @param string $queue The queue name
     * @return void
     */
    public function markJobFailed(string $exception, int | string $jobId, string $payload = "No Payload", string $queue = "default"): void;

    /**
     * Release a job back to the queue after a failure, with a delay
     * @param object|string $job The job to release
     * @param int $timeBetween The delay time in seconds
     * @param string $queue The queue name
     * @return void
     */
    public function releaseJob(object|string $job, int $timeBetween,string $queue = "default"): void;

    /**
     * Requeue a failed job by its UUID
     * @param String $uuid The UUID of the failed job
     * @return void
     */
    public function requeueFailedJob(String $uuid): void;
}
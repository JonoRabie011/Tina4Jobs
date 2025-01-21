<?php

namespace Tina4Jobs\Tina4Queue;

interface Tina4QueueInterface
{
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
    public function markJobCompleted(int $jobId): void;
    public function markJobFailed(string $exception, int $jobId): void;
    public function releaseJob(object|string $job, int $timeBetween): void;
}
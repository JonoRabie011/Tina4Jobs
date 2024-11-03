<?php

namespace Tina4Jobs\Tina4Queue;

interface Tina4QueueInterface
{
    public function addJob(object|string $job, string $queue = "default"): void;
    public function getNextJob(string $queue = "default"): ?object;
    public function markJobCompleted(int $jobId): void;
    public function markJobFailed(string $exception, int $jobId): void;
}
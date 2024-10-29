<?php

namespace Tina4Jobs\Tina4Queue;

use Tina4\Data;

class Tina4DatabaseJob extends Data implements Tina4QueueInterface
{

    private $databaseConnection;


    public function addJob(object|string $job, string $queue = "default"): void
    {

        $this->DBA->exec(" INSERT INTO jobs_queue (queue_name, job_data, status) values(?, ?, ?)", $queue, serialize($job), "pending");

        // TODO: Implement addJob() method.
    }

    public function getNextJob(string $queue = "default"): ?object
    {
        $job = $this->DBA->fetch("SELECT * FROM jobs_queue WHERE queue_name = {$queue} AND status = 'pending' ORDER BY id ASC LIMIT 1", 1)
        ->asArray();

        if (count($job) > 0) {
            return unserialize($job[0]["job_data"]);
        }

        return null;
    }

    public function markJobCompleted(int $jobId): void
    {
        // TODO: Implement markJobCompleted() method.
    }

    public function markJobFailed(int $jobId): void
    {
        // TODO: Implement markJobFailed() method.
    }
}
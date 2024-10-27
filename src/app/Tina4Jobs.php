<?php

namespace Tina4Jobs;

use Redis;

class Tina4Jobs
{


    private Redis $jobsConnection;

    public function __construct()
    {
        $this->jobsConnection = new Redis();

        $this->openConnection();

    }

    public function openConnection()
    {
        $this->jobsConnection->connect($_ENV["REDIS_HOST"], $_ENV["REDIS_PORT"]);
    }

    public function getJobsConnection()
    {
        return $this->jobsConnection;
    }

    /**
     * Add a job to the queue
     * @param $job Object | string Tina4Job
     * @param $queue string The queue to add the job too
     * @throws \RedisException
     *
     */
    public function addJob(object|string $job, string $queue="default")
    {

        $serializeJob = serialize($job);

        $this->jobsConnection->lPush($queue, $serializeJob);
        $this->jobsConnection->close();
    }
}
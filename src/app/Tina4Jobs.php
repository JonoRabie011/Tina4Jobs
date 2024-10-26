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

    public function addJob($queue, $job)
    {

        $serializeJob = serialize($job);

        $this->jobsConnection->lPush($queue, $serializeJob);
        $this->jobsConnection->close();
    }
}
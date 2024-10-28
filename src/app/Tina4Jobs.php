<?php

/**
 * @copyright jrwebdesigns Pty (LTD)
 * @package Tina4Jobs
 * @version 1.0.0
 * @author Jonathan Rabie
 */

namespace Tina4Jobs;

use Redis;
use RedisException;

class Tina4Jobs
{


    /**
     * The redis connection to the jobs queue
     * @var Redis
     */
    private Redis $jobsConnection;

    /**
     * @throws RedisException
     */
    public function __construct()
    {
        $this->jobsConnection = new Redis();

        $this->openConnection();

    }

    /**
     * Open a connection to the redis server
     * @throws RedisException
     */
    private function openConnection()
    {
        $this->jobsConnection->connect($_ENV["REDIS_HOST"], $_ENV["REDIS_PORT"]);
    }

    /**
     * Returns the redis connection allowing you to interact with the jobs queue
     * @return Redis
     */
    public function getJobsConnection()
    {
        return $this->jobsConnection;
    }

    /**
     * Add a job to the queue
     * @param $job Object | string Tina4Job
     * @param $queue string The queue to add the job too (Not implemented yet)
     * @throws RedisException
     *
     */
    public function addJob(object|string $job, string $queue="default")
    {

        $serializeJob = serialize($job);

        $this->jobsConnection->lPush("default", $serializeJob);
        $this->jobsConnection->close();
    }
}
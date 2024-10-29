<?php

/**
 * Redis-based Job Queue Management
 * This class allows you to add jobs to a Redis queue and manage Redis connections.
 *
 * @package Tina4Jobs
 * @version 1.0.1
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
     * Open a connection to the Redis server.
     * Attempts reconnection if connection fails.
     */
    private function openConnection(): void
    {
        try {
            $this->jobsConnection->connect($_ENV["REDIS_HOST"], $_ENV["REDIS_PORT"]);
        } catch (RedisException $e) {
            // Log or handle the connection error as needed
            echo "Failed to connect to Redis: ", $e->getMessage(), "\n";
            // Optionally, implement a retry or fallback strategy
        }
    }

    /**
     * Returns the redis connection allowing you to interact with the jobs queue
     * @return Redis
     */
    public function getJobsConnection(): Redis
    {
        return $this->jobsConnection;
    }

    /**
     * Add a job to the queue.
     * @param object|string $job Tina4Job as object or serialized string
     * @param string $queue The queue to add the job to
     */
    public function addJob(object|string $job, string $queue = "default"): void
    {
        try {
            $serializeJob = serialize($job);
            $this->jobsConnection->lPush("default", $serializeJob);
        } catch (RedisException $e) {
            echo "Failed to add job to queue: ", $e->getMessage(), "\n";
            // Implement further handling if needed, like logging
        }
    }

    /**
     * Close the Redis connection when the instance is destroyed.
     * @throws RedisException
     */
    public function __destruct()
    {
        if ($this->jobsConnection->isConnected()) {
            $this->jobsConnection->close();
        }
    }
}
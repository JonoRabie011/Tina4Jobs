<?php

/**
 * Redis-based Job Queue Management
 * This class allows you to add jobs to a Redis queue and manage Redis connections.
 *
 * @package Tina4Jobs
 * @version 1.0.1
 * @author Jonathan Rabie
 */

namespace Tina4Jobs\Tina4Queue;

use Psr\Cache\InvalidArgumentException;
use Redis;
use RedisException;
use Tina4\Utilities;

class Tina4RedisJob implements Tina4QueueInterface
{

    /**
     * The redis connection to the jobs queue
     * @var Redis
     */
    private Redis $jobsConnection;


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
     * Get the next job from the queue.
     * @param string $queue The queue to get the job from
     * @return object|null The next job in the queue, or null if the queue is empty
     * @throws RedisException
     */
    public function getNextJob(string $queue = "default"): ?object
    {
        $job = $this->jobsConnection->rPop($queue);
        return $job ? unserialize($job) : null;
    }

    public function markJobCompleted(int $jobId): void
    {
        // TODO: Implement markJobCompleted() method.
    }

    /**
     * Add failed job to the failed jobs table
     * @param string $exception
     * @param int $jobId
     * @param string $payload
     * @param string $queue
     * @return void
     */
    public function markJobFailed(string $exception, int $jobId, string $payload = "No Payload", string $queue = "default"): void
    {
        try {
            $failedJob = new \FailedJob();
            $failedJob->uuid = (new Utilities())->getGUID();
            $failedJob->connection = Tina4RedisJob::class;
            $failedJob->queue = $queue;
            $failedJob->payload = $payload;
            $failedJob->exception = $exception;
            $failedJob->failed_at = date("Y-m-d H:i:s");
            $failedJob->save();
        } catch (InvalidArgumentException|\Exception $e) {
            echo "\n\n\n\nFailed to mark job as failed: ", $e->getMessage(), "\n";
        }
    }


    public function releaseJob(object|string $job, int $timeBetween, string $queue = "default"): void
    {
        sleep($timeBetween);

        $this->addJob($job, $queue);
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

    public function requeueFailedJob(string $uuid): void
    {
        // TODO: Implement requeueFailedJob() method.
    }
}
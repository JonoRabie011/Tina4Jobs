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
     * @inheritDoc
     */
    public function addJob(object|string $job, string $queue = "default"): void
    {
        try {
            $serializeJob = serialize($job);
            $this->jobsConnection->lPush($queue, $serializeJob);
        } catch (RedisException $e) {
            echo "Failed to add job to queue: ", $e->getMessage(), "\n";
            // Implement further handling if needed, like logging
        }
    }

    /**
     * @inheritDoc
     */
    public function getNextJob(string $queue = "default"): ?object
    {
        $job = $this->jobsConnection->rPop($queue);

        if (!empty($job)) {
            $currentJobUnSerialized = unserialize(convert_uudecode($job->payload));

            /*
             * Set the job ID on the job object as a GUID since Redis does not have an auto-incrementing ID like a database.
             * This allows us to track the job for completion or failure.
             */
            $currentJobUnSerialized->setJobId((new Utilities())->getGUID());

            return $currentJobUnSerialized;
        } else {
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function markJobCompleted(int $jobId): void
    {
        // TODO: Implement markJobCompleted() method.
    }


    /**
     * @inheritDoc
     */
    public function markJobFailed(string $exception, string|int $jobId = "", string $payload = "No Payload", string $queue = "default"): void
    {
        try {
            $failedJob = new \FailedJob();
            $failedJob->uuid = $jobId ?? (new Utilities())->getGUID();
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


    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function requeueFailedJob(string $uuid): void
    {
        // TODO: Implement requeueFailedJob() method.
    }
}
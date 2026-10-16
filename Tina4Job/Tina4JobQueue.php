<?php

/**
 * Class Tina4JobQueue
 * @package Tina4Jobs
 */

namespace Tina4Jobs;

use Tina4Jobs\Tina4Queue\Tina4JobFactory;

final class Tina4JobQueue
{

    /**
     * Get the queue name for the job
     * If the job has a 'queue' property, use that. Otherwise default to the one passed in
     * @param object $job The job object
     * @return string The queue name
     */
    private static function getQueue($job, $queue): string
    {
        // Read through reflection so a private or protected $queue works too;
        // reading it directly threw "Cannot access private property"
        if (is_object($job) && property_exists($job, 'queue')) {
            $property = new \ReflectionProperty($job, 'queue');

            if ($property->isInitialized($job) && !empty($property->getValue($job))) {
                return (string)$property->getValue($job);
            }
        }

        return $queue;
    }

    /**
     * Add new job to the queue
     * @param object|string|array $jobs The job to add to the queue or an array of jobs
     * @param string $queue The queue to add the job to
     * @return void
     */
    final static function push(object|array $jobs, string $queue = "default"): void
    {
        if (is_array($jobs)) {
            $jobFactory = Tina4JobFactory::createQueueDriver();
            foreach ($jobs as $job) {
                $queueForJob = self::getQueue($job, $queue);
                $jobFactory->addJob($job, $queueForJob);
            }
        } else {
            $queueForJob = self::getQueue($jobs, $queue);
            Tina4JobFactory::createQueueDriver()->addJob($jobs, $queueForJob);
        }
    }

    /**
     * Add new job to the queue that only becomes available after a delay
     * @param object|array $jobs The job to add to the queue or an array of jobs
     * @param int $delay Number of seconds from now before the job can be processed
     * @param string $queue The queue to add the job to
     * @return void
     */
    final static function pushWithDelay(object|array $jobs, int $delay, string $queue = "default"): void
    {
        $jobFactory = Tina4JobFactory::createQueueDriver();
        $availableAt = time() + max(0, $delay);

        if (is_array($jobs)) {
            foreach ($jobs as $job) {
                $queueForJob = self::getQueue($job, $queue);
                $jobFactory->addFutureJob($job, $availableAt, $queueForJob);
            }
        } else {
            $queueForJob = self::getQueue($jobs, $queue);
            $jobFactory->addFutureJob($jobs, $availableAt, $queueForJob);
        }
    }
}
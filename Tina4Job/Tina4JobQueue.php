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
        if (property_exists($job, 'queue')) {
            return $job->queue;
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
            foreach ($jobs as $job) {
                $queueForJob = self::getQueue($job, $queue);
                Tina4JobFactory::createQueueDriver()->addJob($job, $queueForJob);
            }
        } else {
            $queueForJob = self::getQueue($jobs, $queue);
            Tina4JobFactory::createQueueDriver()->addJob($jobs, $queueForJob);
        }
    }
}
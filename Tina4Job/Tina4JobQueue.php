<?php

/**
 * Class Tina4JobQueue
 * @package Tina4Jobs
 */

namespace Tina4Jobs;

use Tina4Jobs\Tina4Queue\Tina4JobFactory;

class Tina4JobQueue
{

    /**
     * Add new job to the queue
     * @param object|string|array $jobs The job to add to the queue or an array of jobs
     * @param string $queue The queue to add the job to
     * @return void
     */
    protected static function push(object|array $jobs, string $queue = "default"): void
    {
        if (is_array($jobs)) {
            foreach ($jobs as $job) {
                Tina4JobFactory::createQueueDriver()->addJob($job, $queue);
            }
        } else {
            Tina4JobFactory::createQueueDriver()->addJob($jobs, $queue);
        }
    }
}
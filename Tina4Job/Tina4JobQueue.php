<?php

namespace Tina4Jobs;

use Tina4Jobs\Tina4Queue\Tina4JobFactory;

class Tina4JobQueue
{

    static function push(object|string|array $jobs, string $queue = "default"): void
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
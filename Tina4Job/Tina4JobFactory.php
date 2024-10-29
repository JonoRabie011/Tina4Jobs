<?php

namespace Tina4Jobs;

use Tina4Jobs\Tina4Queue\Tina4DatabaseJob;
use Tina4Jobs\Tina4Queue\Tina4RedisJob;

class Tina4JobFactory
{
    public static function createQueueDriver(): Tina4DatabaseJob|Tina4RedisJob
    {
//        $driver = $_ENV['QUEUE_DRIVER'] ?? 'database';
        $driver = $_ENV['QUEUE_DRIVER'] ?? 'redis';

        return match ($driver) {
            'redis' => new Tina4RedisJob(),
            default => new Tina4DatabaseJob(),
        };
    }
}

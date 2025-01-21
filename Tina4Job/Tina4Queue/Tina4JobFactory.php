<?php

namespace Tina4Jobs\Tina4Queue;

class Tina4JobFactory
{
    public static function createQueueDriver(): Tina4DatabaseJob|Tina4RedisJob
    {
        $driver = $_ENV['QUEUE_DRIVER'] ?? 'database';
//        $driver = $_ENV['QUEUE_DRIVER'] ?? 'redis';

        return match ($driver) {
            'redis' => new Tina4RedisJob(),
            'kafka' => new Tina4KafkaJob(),
            default => new Tina4DatabaseJob(),
        };
    }
}

<?php

namespace Tina4Jobs;

use Tina4Jobs\Tina4Queue\Tina4JobFactory;

Trait Tina4InteractQueue
{
    protected int $timeBetweenAttempts = 20;

    function release()
    {
//        Tina4JobFactory::createQueueDriver()->releaseJob($this);
    }
}
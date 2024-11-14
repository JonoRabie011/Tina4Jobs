<?php

namespace Tina4Jobs;

Trait Tina4InteractQueue
{
    public $job;

    function release($delay)
    {
        //@TODO Create function that will release a job back into the queue for later retry
    }
}
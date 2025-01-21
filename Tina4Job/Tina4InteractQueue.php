<?php

namespace Tina4Jobs;

Trait Tina4InteractQueue
{
    protected int $timeBetweenAttempts = 20;

    function release()
    {
        //@TODO Create function that will release a job back into the queue for later retry
    }
}
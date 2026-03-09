<?php

/**
 * Interface Tina4Job
 * @package Tina4Jobs
 */

namespace Tina4Jobs;

interface Tina4JobInterface
{

    /**
     * This is the method that will be called when the job is processed.
     * You should put the logic for your job in this method.
     *
     * @return void
     */
    public function handle(): void;

}
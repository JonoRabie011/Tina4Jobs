<?php

/**
 * Interface Tina4Job
 * @package Tina4Jobs
 */

namespace Tina4Jobs;

interface Tina4Job
{

    /**
     * Handle the job.
     *
     * @return void
     */
    public function handle(): void;

}
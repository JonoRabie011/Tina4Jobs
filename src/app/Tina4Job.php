<?php

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
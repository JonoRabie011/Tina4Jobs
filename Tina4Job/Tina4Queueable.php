<?php

namespace Tina4Jobs;

Trait Tina4Queueable
{
    /**
     * The id of the job used for a datatbase job
     * @var int
     */
    private int $jobId;

    /**
     * The number of times the job may be attempted before it is marked as failed
     * @var int
     */
    public int $attempts = 1;

    /**
     * @var int The number of seconds before a job needs to be attempted again
     */
    public int $timeBetweenAttempts = 0;


    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }

    public function getJobId()
    {
        return $this->jobId;
    }

}
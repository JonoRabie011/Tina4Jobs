<?php

namespace Tina4Jobs;

Trait Tina4Queueable
{
    /**
     * The id of the job used for a datatbase job
     * @var int
     */
    private int $jobId;

    public int $attempt;

    public int $attempts;

    public int $timeBetweenAttempts;


    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }

    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * Get allowed attempts for a job
     * @return int
     */
    public function getAttempts(): int
    {
        if(empty($this->attempts)) {
            $this->attempts = 1;
        }

        return $this->attempts;
    }

    public function attempt(): bool {
        $attempts = $this->getAttempts();

        if(empty($this->attempt)) {
            $this->attempt = 1;
        }

        if(($this->attempt + 1) > $attempts) {
            return false;
        }

        $this->attempt++;

        return true;
    }

    public function getAttempt() {
        if(empty($this->attempt)) {
            $this->attempt = 1;
        }

        return $this->attempt;
    }

}
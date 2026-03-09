<?php

namespace Tina4Jobs\Traits;

Trait Tina4Queueable
{
    private string|int $jobId;

    private int $attempt;

    protected int $timeoutAfterTime;

    /**
     * Set the job id for the job
     * @param $jobId
     * @return void
     */
    public function setJobId($jobId): void
    {
        $this->jobId = $jobId;
    }

    public function getJobId(): int|string
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
            $this->attempts = 2;
        }

        return $this->attempts;
    }

    /**
     * Check if the job can be attempted again
     * @return bool
     */
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

    /**
     * Get the current attempt number
     * @return int
     */
    public function getAttempt(): int
    {
        if (empty($this->attempt)) {
            $this->attempt = 1;
        }

        return $this->attempt;
    }

    public function getTimeBetweenAttempts(): int
    {
        if(empty($this->timeBetweenAttempts)) {
            $this->timeBetweenAttempts = 20;
        }

        return $this->timeBetweenAttempts;
    }

    public function getTimeoutAfterTime(): int
    {
        if(empty($this->timeoutAfterTime)) {
            $this->timeoutAfterTime = 120;
        }

        return $this->timeoutAfterTime;
    }
}
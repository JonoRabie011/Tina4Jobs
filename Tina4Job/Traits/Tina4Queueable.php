<?php

namespace Tina4Jobs\Traits;

trait Tina4Queueable
{
    private string|int|null $jobId = null;

    private int $attempt;

    protected int $timeoutAfterTime;

    /**
     * Set the job id for the job
     * @param int|string $jobId
     * @return void
     */
    public function setJobId(int|string $jobId): void
    {
        $this->jobId = $jobId;
    }

    /**
     * The id of the job in its queue, or null before it has been taken off a queue
     * @return int|string|null
     */
    public function getJobId(): int|string|null
    {
        return $this->jobId;
    }

    /**
     * Get allowed attempts for a job
     * @return int
     */
    public function getAttempts(): int
    {
        // Read without assigning: jobs that don't declare $attempts would otherwise
        // get a dynamic property, which is deprecated as of PHP 8.2
        return empty($this->attempts) ? 2 : $this->attempts;
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

    /**
     * Start counting attempts again, used when a failed job is requeued by hand
     * @return void
     */
    public function resetAttempts(): void
    {
        $this->attempt = 1;
    }

    public function getTimeBetweenAttempts(): int
    {
        return empty($this->timeBetweenAttempts) ? 20 : $this->timeBetweenAttempts;
    }

    public function getTimeoutAfterTime(): int
    {
        if(empty($this->timeoutAfterTime)) {
            $this->timeoutAfterTime = 120;
        }

        return $this->timeoutAfterTime;
    }
}

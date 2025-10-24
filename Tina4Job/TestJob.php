<?php

namespace Tina4Jobs;

class TestJob implements Tina4Job
{
    use Tina4Queueable; // Use the trait to get queue-related functionality

    /**
     * @var int Number of attempts allowed for the job
     */
    protected int $attempts = 2;
    /**
     * @var int Time in seconds between attempts
     */
    protected int $timeBetweenAttempts = 2;
    /**
     * @var int Timeout duration in seconds for the job
     */
    protected int $timeoutAfterTime = 60;
    private $user;

    public $queue = "Another";

    public function __construct($payload = [])
    {
        $this->user = ["name" => "John", "surname" => "Doe",
            ...$payload];
    }

    public function handle(): void
    {

        if($this->getAttempt()  <= 1) {
            throw new \Exception("Job failed");
        }

        file_put_contents("test.txt", json_encode($this->user) . "\n\n", FILE_APPEND);
    }
}
<?php

namespace Tina4Jobs;

class TestJob implements Tina4Job
{
    use Tina4Queueable;

    protected int $attempts = 2;
    protected int $timeBetweenAttempts = 2;
    protected int $timeoutAfterTime = 60;
    private $user;

    public function __construct($payload = [])
    {
        $this->user = ["name" => "John", "surname" => "Doe",
            ...$payload];
    }

    public function handle(): void
    {

        sleep(600); // Simulate a long process
        if($this->getAttempt()  <= 1) {
            throw new \Exception("Job failed");
        }

        file_put_contents("test.txt", json_encode($this->user) . "\n\n", FILE_APPEND);
    }
}
<?php

namespace Tina4Jobs;

class TestJob implements Tina4Job
{
    use Tina4Queueable;

    protected int $attempts = 2;
    protected int $timeBetweenAttempts = 2;

    private $user;

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
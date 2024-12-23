<?php

namespace Tina4Jobs;

class TestJob implements Tina4Job
{
    use Tina4Queueable;

    public int $attempts = 6;
    public int $timeBetweenAttempts = 20;

    private $user;

    public function __construct($payload = [])
    {
        $this->user = ["name" => "John", "surname" => "Doe",
            ...$payload];
    }

    public function handle(): void
    {
        file_put_contents("test.txt", json_encode($this->user) . "\n\n", FILE_APPEND);
    }
}
## Welcome to the Tina4 Jobs Module


This package uses a redis server to manage the jobs and to run them on a schedule. It is designed to be a simple way to manage jobs and to be able to run them on a schedule.

## Installation

To install the package you can use the following command

```bash
composer require tina4components/tina4jobsmodule
```

## Usage

There are two variables that you need to set in your .env file

```dotenv
  REDIS_HOST: <host address to your redis server>
  REDIS_PORT: <ports to your redis server: Default usually on redis is :6379>
```

When creating a job you need to create a class that implements the `Tina4Job` interface and implement the `handle()` method. 
The `handle()` method is the method that will be called when the job is run.

The following is an example of a job that writes a user to a file, All job classes need to implement the Tina4Job interface.

```php
<?php

namespace Tina4Jobs;

use Tina4Jobs\Tina4Job;

class TestJob implements Tina4Job
{

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
```

Creating a job is as simple as creating a new instance of Tina4Jobs and adding it to the jobs list.

```php
$jobs = Tina4Jobs\Tina4JobFactory::createQueueDriver();
$jobs->addJob(new \Tina4Jobs\TestJob(
    [
        "surname" => "Smith", 
        "age" => 30, 
        "country" => "South Africa"
    ]
), "queue_name");
```

To run the jobs there is a jobs service in the Tina4Jobs module that you can use to run the jobs.

```bash
composer start-jobs
```

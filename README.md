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
    use Tina4Queueable; // This is a trait that is used to make the job queueable

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
\Tina4Jobs\Tina4JobQueue::push((new \Tina4Jobs\TestJob(
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

## Running jobs on linux server as a service

To run the jobs on a linux server you can use the following command

Step 1: `cd /etc/systemd/system`

Step 2: Create a new service file `nano tina4jobs.service` and add the following content

```
[Unit]
Description=Tina4Jobs Service
After=mysqld.service
StartLimitIntervalSec=0
[Service]
Type=simple
Restart=always
RestartSec=3
User=integration
WorkingDirectory=<path to your project directory>
ExecStart=php bin/tina4jobs
[Install]
WantedBy=multi-user.target
```

Step 3: Reload the systemd daemon `systemctl daemon-reload`

Step 4: Start the service `systemctl start tina4jobs.service`

Step 5: Check the status of the service `systemctl status tina4jobs.service`

Step 6: Enable the service to start on boot `systemctl enable tina4jobs.service`

Notes:
- You can change the user to the user that you want to run the service as.
- You can change the path to the project directory where the `bin/tina4jobs` file is located.
- You can change the description of the service to whatever you want.
- You can change the name of the service to whatever you want.
- To stop the service you can use `systemctl stop tina4jobs.service`
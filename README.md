## Welcome to the Tina4 Jobs Module

A background job queue for Tina4 projects. Jobs are queued in your database (default) or in Redis, and run by a worker process.

Requires PHP 8.2 or newer with the `pcntl` extension.

## Installation

To install the package you can use the following command

```bash
composer require tina4components/tina4jobsmodule
```

Create the queue tables with the migration for your database from `migrations/` (`mysql/`, `postgresql/`, or the root one for SQLite).

## Usage (DATABASE QUEUE)

The default. Jobs are stored in `tina4_jobs`, using your project's `$DBA` connection.

```dotenv
QUEUE_DRIVER=database
```

## Usage (REDIS QUEUE)

```dotenv
QUEUE_DRIVER=redis
REDIS_HOST=<host address to your redis server>
REDIS_PORT=6379
# Optional
REDIS_PASSWORD=
REDIS_DATABASE=0
TINA4_JOBS_REDIS_PREFIX=tina4jobs:
```

Keys are prefixed (`tina4jobs:queue:<queue>`, `tina4jobs:delayed:<queue>`) so the queue can share a Redis with your cache. Failed jobs are still recorded in the database table `tina4_failed_jobs`.

Note that Redis removes a job when a worker takes it, so a job is lost if its worker is killed mid-run. Use the database driver for jobs that must not be lost.

## General Usage

When creating a job you need to create a class that extends the `Tina4Job` abstract class and implements the `handle()` method.
The `handle()` method is the method that will be called when the job is run.

The following is an example of a job that writes a user to a file.

```php
<?php

namespace Tina4Jobs;

use Tina4Jobs\Tina4Job;

class TestJob extends Tina4Job
{
    /*
     * You can set a custom queue name for the job
     * if this is set this will be the preferred queue for this job
     * and override any queue name passed when adding the job to the queue
     * aswell as the default queue. Any visibility works.
     */
    protected string $queue = "CustomQueueName";

    /*
     * Optional. Declaring $attempts turns on retries: a failed job runs again after
     * $timeBetweenAttempts seconds, up to $attempts runs in total. Jobs without it are
     * not retried, because jobs with side effects (payments, emails) may not be safe to repeat.
     */
    protected int $attempts = 3;
    protected int $timeBetweenAttempts = 30;

    /*
     * Optional. Seconds the job may run before it is stopped and counted as failed (default 120).
     */
    protected int $timeoutAfterTime = 60;

    private array $user;

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

Jobs are serialized onto the queue, so keep their properties to plain data (ids, strings, arrays) rather than connections or closures, and use a named class: anonymous classes cannot be serialized.

Creating a job is as simple as creating a new instance of TestJob class and adding it to the jobs list.

```php
\Tina4Jobs\Tina4JobQueue::push(new \Tina4Jobs\TestJob(
    [
        "surname" => "Smith",
        "age" => 30,
        "country" => "South Africa"
    ]
), "queue_name");
```

To run a job later, give the delay in seconds:

```php
// Runs in about an hour
\Tina4Jobs\Tina4JobQueue::pushWithDelay(new \Tina4Jobs\TestJob(), 3600);
```

## Running the worker

Listens to the queue name "default" if no queue name is provided.
```bash
composer start-jobs
```

Custom queue name
```bash
composer start-jobs --queue=queue_name
```

Several queues, polled in order, so `high` jobs always go first:
```bash
php vendor/bin/tina4jobs --queue=high,default
```

Every option can be set on the command line or in the environment:

| Option | Environment | Default | |
|---|---|---|---|
| `--queue` | `TINA4_JOBS_QUEUE` | `default` | Comma separated queues, highest priority first |
| `--workers` | `TINA4_JOBS_WORKERS` | `10` | Jobs run at the same time |
| `--sleep` | `TINA4_JOBS_SLEEP` | `2` | Seconds to wait when the queues are empty |
| | `TINA4_JOBS_MAX_MEMORY` | `256` | MB the worker may use before restarting itself |
| | `TINA4_JOBS_STALE_AFTER` | `3600` | Seconds a job may stay reserved before it is treated as abandoned (see below). Keep it above your longest job timeout |
| | `TINA4_JOBS_HEARTBEAT` | `<tmp>/tina4jobs-<queues>.heartbeat` | File touched on every poll, for health checks |

### How jobs run

- **Each job runs in its own PHP process**, started fresh from your project (`index.php`). Jobs cannot leak state into each other, and never share the worker's database or Redis connection.
- **Each job is claimed by exactly one worker**, so you can run several workers on the same queue.
- **Timeouts:** a job that runs past `$timeoutAfterTime` is stopped and counted as a failure. If it cannot be interrupted, the worker kills its process 30 seconds later.
- **Failures** are retried if the job declares `$attempts`, otherwise moved to `tina4_failed_jobs` with the full exception and trace. A job whose process crashes (a fatal error, `exit()`, out of memory) is handled the same way.
- **Abandoned jobs:** if a worker is killed mid-job (a power cut, `kill -9`), the job stays reserved. Workers check every minute for jobs reserved longer than `TINA4_JOBS_STALE_AFTER` and retry or fail them.
- **Requeue a failed job:** `Tina4JobFactory::createQueueDriver()->requeueFailedJob($uuid)`. It starts again with a fresh attempt count.

### Stopping the worker

Send `SIGTERM` (or press Ctrl+C). The worker stops taking new jobs, waits for running jobs to finish, then exits. Give your process manager a stop timeout longer than your longest job: `TimeoutStopSec=` for systemd, `stop_grace_period` for Docker Compose.

The worker exits on its own, so your process manager should restart it, when:
- it goes over `TINA4_JOBS_MAX_MEMORY` (after finishing running jobs)
- it fails to read the queue 10 times in a row, usually because its database connection died

### Logs

One timestamped line per event: info on stdout, errors on stderr.

```
[2026-10-16 08:48:30] INFO [default] [1] Started job 7 (App\Jobs\EmailJob) in process 12
[2026-10-16 08:48:30] INFO [default] [12] Job 7 completed in 0.31s
```

## Running the worker in Docker

Run the worker directly rather than through `composer start-jobs`: it should be PID 1 so it receives the stop signal. Composer does not pass signals on.

```yaml
jobs:
  image: your-app-image
  command: ["php", "-d", "memory_limit=512M", "vendor/bin/tina4jobs", "--queue=default"]
  # Images built on php:*-apache set STOPSIGNAL SIGWINCH, which the worker ignores
  stop_signal: SIGTERM
  stop_grace_period: 150s
  restart: unless-stopped
  healthcheck:
    # Healthy while the worker is still polling
    test: ["CMD-SHELL", "f=/tmp/tina4jobs-default.heartbeat; [ -f $$f ] && [ $$(( $$(date +%s) - $$(stat -c %Y $$f) )) -lt 120 ]"]
    interval: 30s
    start_period: 30s
```

If your app image has its own `HEALTHCHECK` (for example a web request), override it as above; nothing listens for HTTP in the jobs container.

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
ExecStart=php vendor/bin/tina4jobs
KillSignal=SIGTERM
TimeoutStopSec=150
[Install]
WantedBy=multi-user.target
```

Step 3: Reload the systemd daemon `systemctl daemon-reload`

Step 4: Start the service `systemctl start tina4jobs.service`

Step 5: Check the status of the service `systemctl status tina4jobs.service`

Step 6: Enable the service to start on boot `systemctl enable tina4jobs.service`

Notes:
- You can change the user to the user that you want to run the service as.
- You can change the path to the project directory where the `vendor/bin/tina4jobs` file is located.
- You can change the description of the service to whatever you want.
- You can change the name of the service to whatever you want.
- To stop the service you can use `systemctl stop tina4jobs.service`

## Upgrading from 0.0.x

- **Completed jobs are now removed.** Earlier versions never deleted a completed job's row, so `tina4_jobs` may hold old rows that are still reserved. Delete them with the worker stopped (so no job is mid-run), or they will be moved to `tina4_failed_jobs` as abandoned an hour after the upgrade (they will not run again either way):
  ```sql
  DELETE FROM tina4_jobs WHERE reserved_at > 0;
  ```
- **Redis keys are prefixed.** Jobs still waiting in Redis under the bare queue name are not picked up. Drain the queue before upgrading if you use the Redis driver.
- **`pushWithDelay()` takes the delay in seconds** as its second argument. It previously crashed on every call.

<?php
namespace Tina4Jobs\test;

use PHPUnit\Framework\TestCase;
use Tina4Jobs\TestJob;
use Tina4Jobs\Tina4Queue\Tina4RedisJob;

class Tina4RedisJobTest extends TestCase
{

    use Tina4JobsTestHelperTrait;

    private string $queue = "tina4jobs-test";

    protected function setUp(): void
    {
        $_ENV['REDIS_HOST'] = getenv('REDIS_HOST') ?: 'localhost';
        $_ENV['REDIS_PORT'] = getenv('REDIS_PORT') ?: '6379';

        try {
            $redis = new \Redis();
            $redis->connect($_ENV['REDIS_HOST'], (int)$_ENV['REDIS_PORT'], 1);
            $redis->del($this->queue);
            $redis->close();
        } catch (\RedisException $e) {
            $this->markTestSkipped("Redis is not available: " . $e->getMessage());
        }
    }

    public function testAddedJobCanBeReadBack()
    {
        $this->printHeader("Testing Redis job round trip");

        try {
            $redisJob = new Tina4RedisJob();
            $redisJob->addJob(new TestJob(["name" => "Jane"]), $this->queue);

            $nextJob = $redisJob->getNextJob($this->queue);
            $this->assertInstanceOf(TestJob::class, $nextJob);
            $this->assertNotEmpty($nextJob->getJobId(), "Job should be given a GUID job id");

            $this->assertNull($redisJob->getNextJob($this->queue), "Queue should now be empty");

            $this->printResult("Redis job round trip", true);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->printResult("Redis job round trip", false);
            throw $e;
        }
    }
}

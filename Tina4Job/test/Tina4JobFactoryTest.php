<?php
namespace Tina4Jobs\test;

use PHPUnit\Framework\TestCase;
use Tina4Jobs\Tina4Queue\Tina4DatabaseJob;
use Tina4Jobs\Tina4Queue\Tina4JobFactory;
use Tina4Jobs\Tina4Queue\Tina4RedisJob;

class Tina4JobFactoryTest extends TestCase
{

    use Tina4JobsTestHelperTrait;

    protected function setUp(): void
    {
        // Clear environment before each test for consistent state
        unset($_ENV['QUEUE_DRIVER']);
        // Set default Redis configuration for testing
        $_ENV['REDIS_HOST'] = 'localhost';
        $_ENV['REDIS_PORT'] = '6379';
        $_ENV['REDIS_PASSWORD'] = '';
    }

    protected function tearDown(): void
    {
        // Clean up after tests
        unset($_ENV['QUEUE_DRIVER']);
        unset($_ENV['REDIS_HOST']);
        unset($_ENV['REDIS_PORT']);
        unset($_ENV['REDIS_PASSWORD']);
    }



    public function testCreatesDatabaseJobByDefault()
    {
        $this->printHeader("Testing default job creation");

        try {
            $job = Tina4JobFactory::createQueueDriver();
            $this->assertInstanceOf(Tina4DatabaseJob::class, $job);
            $this->printResult("Default job type", true);
        } catch (\Throwable $e) {
            $this->printResult("Default job type", false);
            throw $e;
        }
    }

    public function testCreatesDatabaseJobWhenSetExplicitly()
    {

        $this->printHeader("Testing explicit database job creation");

        $_ENV['QUEUE_DRIVER'] = 'database';
        try {
            $job = Tina4JobFactory::createQueueDriver();
            $this->assertInstanceOf(Tina4DatabaseJob::class, $job);
            $this->printResult("Database job type", true);
        } catch (\Throwable $e) {
            $this->printResult("Database job type", false);
            throw $e;
        }
    }

    public function testCreatesRedisJob()
    {
        $this->printHeader("Testing redis job creation");

        // For unit testing, we'll check the class instantiation without connecting
        $_ENV['QUEUE_DRIVER'] = 'redis';

        try {
            // If your code tries to connect immediately, you might need to mock the Redis class
            // This test now just verifies the factory returns the correct class type
            $job = Tina4JobFactory::createQueueDriver();
            $this->assertInstanceOf(Tina4RedisJob::class, $job);
            $this->printResult("Redis job type", true);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->printResult("Redis job type", false);
            throw $e;
        }
    }

    public function testHandlesInvalidDriverType()
    {
        $this->printHeader("Testing invalid driver type handling");

        $_ENV['QUEUE_DRIVER'] = 'nonsense';
        try {
            $job = Tina4JobFactory::createQueueDriver();
            // Should default to database job when invalid type is provided
            $this->assertInstanceOf(Tina4DatabaseJob::class, $job);
            $this->printResult("Invalid driver type", true);
        } catch (\Throwable $e) {
            $this->printResult("Invalid driver type", false);
            throw $e;
        }
    }
}
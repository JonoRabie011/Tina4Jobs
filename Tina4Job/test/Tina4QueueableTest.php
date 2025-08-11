<?php
namespace Tina4Jobs\test;

use PHPUnit\Framework\TestCase;
use Tina4Jobs\Tina4Queueable;

class Tina4QueueableTest extends TestCase
{

    use Tina4JobsTestHelperTrait;

    // Helper class that uses the trait
    private $queueableObject;

    protected function setUp(): void
    {
        // Create a test class that uses the trait
        $this->queueableObject = new class {
            use Tina4Queueable;
        };
    }

    public function testJobIdGetterAndSetter()
    {
        $this->printHeader("Testing JobId getter and setter");

        try {
            $testId = 123;
            $this->queueableObject->setJobId($testId);
            $this->assertEquals($testId, $this->queueableObject->getJobId());
            $this->printResult("JobId getter and setter", true);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->printResult("JobId getter and setter", false);
            throw $e;
        }
    }

    public function testDefaultAttempts()
    {
        $this->printHeader("Testing default attempts value");

        try {
            $this->assertEquals(2, $this->queueableObject->getAttempts());
            $this->printResult("Default attempts value", true);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->printResult("Default attempts value", false);
            throw $e;
        }
    }

    public function testInitialAttemptValue()
    {
        $this->printHeader("Testing initial attempt value");

        try {
            $this->assertEquals(1, $this->queueableObject->getAttempt());
            $this->printResult("Initial attempt value", true);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->printResult("Initial attempt value", false);
            throw $e;
        }
    }

    public function testAttemptIncrementsCorrectly()
    {
        $this->printHeader("Testing attempt increments correctly");

        try {
            // First attempt should return true (can try again)
            $this->assertTrue($this->queueableObject->attempt());
            // Attempt should be incremented to 2
            $this->assertEquals(2, $this->queueableObject->getAttempt());
            // Second attempt should return false (no more attempts)
            $this->assertFalse($this->queueableObject->attempt());
            $this->printResult("Attempt increments correctly", true);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->printResult("Attempt increments correctly", false);
            throw $e;
        }
    }

    public function testDefaultTimeBetweenAttempts()
    {
        $this->printHeader("Testing default time between attempts");

        try {
            $this->assertEquals(20, $this->queueableObject->getTimeBetweenAttempts());
            $this->printResult("Default time between attempts", true);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->printResult("Default time between attempts", false);
            throw $e;
        }
    }

    public function testCustomAttemptsValue()
    {
        $this->printHeader("Testing custom attempts value");

        try {
            // Create a class that uses the trait but overrides the attempts value
            $customObject = new class {
                use Tina4Queueable;// Custom attempts value

                protected int $attempts = 26;
            };
            
            $this->assertEquals(26, $customObject->getAttempts());
            $this->printResult("Custom attempts value", true);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->printResult("Custom attempts value", false);
            throw $e;
        }
    }


    public function testTimeoutAfterTime()
    {
        $this->printHeader("Testing get timeout value");

        try {
            // Create a class that uses the trait but overrides the attempts value
            $customObject = new class {
                use Tina4Queueable;// Custom attempts value

                protected int $timeOutAfter = 600;
            };

            $this->assertEquals(600, $customObject->getTimeoutAfterTime());
            $this->printResult("Custom get timeout value", true);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->printResult("Custom get timeout value", false);
            throw $e;
        }
    }
}
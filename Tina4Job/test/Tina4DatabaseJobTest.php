<?php
namespace Tina4Jobs\test;

use PHPUnit\Framework\TestCase;
use Tina4Jobs\Tina4Queue\Tina4DatabaseJob;

class Tina4DatabaseJobTest extends TestCase
{

    use Tina4JobsTestHelperTrait;

    public function testTest() {
        $this->printHeader('Testing Tina4DatabaseJob');
    }
//    private Tina4DatabaseJob $databaseJob;
//
//    protected function setUp(): void
//    {
//        $this->databaseJob = new Tina4DatabaseJob();
//    }
//
//    protected function tearDown(): void
//    {
//        $this->databaseJob = null;
//    }
//
//    /**
//     * Helper function to print colorful output
//     */
//    protected function printResult(string $testName, bool $passed): void
//    {
//        $passText = $passed ? "\033[32mPASSED\033[0m" : "\033[31mFAILED\033[0m";
//        echo sprintf("%-40s %s\n", $testName, $passText);
//        echo "---------------------------------------------\n";
//    }
//
//    public function testDatabaseJobInitialization()
//    {
//        echo "\n\033[1mTesting database job initialization:\033[0m\n";
//
//        try {
//            $this->assertInstanceOf(Tina4DatabaseJob::class, $this->databaseJob);
//            $this->printResult("Database job initialization", true);
//        } catch (\Throwable $e) {
//            echo "Error: " . $e->getMessage() . "\n";
//            $this->printResult("Database job initialization", false);
//            throw $e;
//        }
//    }
//
//    public function testAddJob()
//    {
//        echo "\n\033[1mTesting addJob method:\033[0m\n";
//
//        try {
//            $testJob = new \stdClass();
//            $testJob->name = "Test Job";
//            $testJob->data = ["key" => "value"];
//
//            $jobId = $this->databaseJob->addJob($testJob);
//            $this->assertTrue($jobId > 0, "Job should be added with a valid ID");
//
//            $this->printResult("Add job to queue", true);
//        } catch (\Throwable $e) {
//            echo "Error: " . $e->getMessage() . "\n";
//            $this->printResult("Add job to queue", false);
//            throw $e;
//        }
//    }
//
//    public function testGetNextJob()
//    {
//        echo "\n\033[1mTesting getNextJob method:\033[0m\n";
//
//        try {
//            // First add a job to ensure there's something to fetch
//            $testJob = new \stdClass();
//            $testJob->name = "Test Job for Fetching";
//            $this->databaseJob->addJob($testJob);
//
//            $job = $this->databaseJob->getNextJob();
//            $this->assertNotNull($job, "Should retrieve a job from queue");
//
//            $this->printResult("Get next job from queue", true);
//        } catch (\Throwable $e) {
//            echo "Error: " . $e->getMessage() . "\n";
//            $this->printResult("Get next job from queue", false);
//            throw $e;
//        }
//    }
//
//    public function testMarkJobCompleted()
//    {
//        echo "\n\033[1mTesting markJobCompleted method:\033[0m\n";
//
//        try {
//            // Add a job to get a valid ID
//            $testJob = new \stdClass();
//            $testJob->name = "Job to Complete";
//            $jobId = $this->databaseJob->addJob($testJob);
//
//            $result = $this->databaseJob->markJobCompleted($jobId);
//            $this->assertTrue($result, "Job should be marked as completed");
//
//            $this->printResult("Mark job as completed", true);
//        } catch (\Throwable $e) {
//            echo "Error: " . $e->getMessage() . "\n";
//            $this->printResult("Mark job as completed", false);
//            throw $e;
//        }
//    }
//
//    public function testMarkJobFailed()
//    {
//        echo "\n\033[1mTesting markJobFailed method:\033[0m\n";
//
//        try {
//            // Add a job to get a valid ID
//            $testJob = new \stdClass();
//            $testJob->name = "Job to Fail";
//            $jobId = $this->databaseJob->addJob($testJob);
//
//            $result = $this->databaseJob->markJobFailed("Test exception message", $jobId);
//            $this->assertTrue($result, "Job should be marked as failed");
//
//            $this->printResult("Mark job as failed", true);
//        } catch (\Throwable $e) {
//            echo "Error: " . $e->getMessage() . "\n";
//            $this->printResult("Mark job as failed", false);
//            throw $e;
//        }
//    }
//
//    public function testReleaseJob()
//    {
//        echo "\n\033[1mTesting releaseJob method:\033[0m\n";
//
//        try {
//            // Create a job object with required methods
//            $jobObject = new class {
//                private $jobId;
//
//                public function __construct() {
//                    $this->jobId = rand(1000, 9999);
//                }
//
//                public function getJobId() {
//                    return $this->jobId;
//                }
//
//                public function getAttempt() {
//                    return 1;
//                }
//            };
//
//            $result = $this->databaseJob->releaseJob($jobObject, 60);
//            $this->assertTrue($result, "Job should be released back to queue");
//
//            $this->printResult("Release job back to queue", true);
//        } catch (\Throwable $e) {
//            echo "Error: " . $e->getMessage() . "\n";
//            $this->printResult("Release job back to queue", false);
//            throw $e;
//        }
//    }
}
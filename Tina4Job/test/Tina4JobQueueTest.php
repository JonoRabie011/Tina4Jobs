<?php
namespace Tina4Jobs\test;

use PHPUnit\Framework\TestCase;
use Tina4Jobs\TestJob;
use Tina4Jobs\Tina4JobQueue;
use Tina4Jobs\Tina4Queue\Tina4DatabaseJob;

class Tina4JobQueueTest extends TestCase
{

    use Tina4JobsTestHelperTrait;

    private string $dbFile;

    protected function setUp(): void
    {
        global $DBA;

        unset($_ENV['QUEUE_DRIVER']);

        $this->dbFile = tempnam(sys_get_temp_dir(), 'tina4jobs') . '.db';
        $DBA = new \Tina4\DataSQLite3($this->dbFile);
        $DBA->exec("create table tina4_jobs (
                        id integer primary key autoincrement,
                        queue text not null,
                        payload text not null,
                        attempts integer not null,
                        reserved_at integer,
                        available_at integer not null,
                        created_at integer not null
                    )");
        $DBA->commit();
    }

    protected function tearDown(): void
    {
        global $DBA;

        $DBA->close();
        $DBA = null;
        @unlink($this->dbFile);
    }

    public function testPushWithDelaySetsAvailableAtInTheFuture()
    {
        $this->printHeader("Testing pushWithDelay");

        try {
            $before = time();
            Tina4JobQueue::pushWithDelay(new TestJob(), 3600);

            $savedJob = (new \Job())->load("id > 0");
            $this->assertNotFalse($savedJob, "Delayed job should be saved");
            $this->assertEquals("Another", $savedJob->queue, "Job's own queue property should be used");
            $this->assertGreaterThanOrEqual($before + 3600, (int)$savedJob->availableAt);
            $this->assertLessThanOrEqual(time() + 3600, (int)$savedJob->availableAt);

            $this->assertNull(
                (new Tina4DatabaseJob())->getNextJob("Another"),
                "Delayed job should not be picked up before it is available"
            );

            $this->printResult("Push job with delay", true);
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->printResult("Push job with delay", false);
            throw $e;
        }
    }
}

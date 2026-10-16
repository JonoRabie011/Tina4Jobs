<?php
namespace Tina4Jobs\test;

use PHPUnit\Framework\TestCase;
use Tina4Jobs\TestJob;
use Tina4Jobs\Tina4Queue\Tina4DatabaseJob;
use Tina4Jobs\Tina4Queue\Tina4JobPayload;

class Tina4DatabaseJobTest extends TestCase
{

    use Tina4JobsTestHelperTrait;

    private string $dbFile;

    private Tina4DatabaseJob $databaseJob;

    protected function setUp(): void
    {
        global $DBA;

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
        $DBA->exec("create table tina4_failed_jobs (
                        id integer primary key autoincrement,
                        uuid text not null,
                        connection text not null,
                        queue text not null,
                        payload text not null,
                        exception text not null,
                        failed_at text
                    )");
        $DBA->commit();

        $this->databaseJob = new Tina4DatabaseJob();
    }

    protected function tearDown(): void
    {
        global $DBA;

        $DBA->close();
        $DBA = null;
        @unlink($this->dbFile);
    }

    public function testGetNextJobClaimsOldestAvailableJobOnce()
    {
        $this->printHeader("Testing getNextJob claims each job once");

        $this->databaseJob->addFutureJob(new TestJob(["name" => "second"]), time() - 10, "Another");
        $this->databaseJob->addFutureJob(new TestJob(["name" => "first"]), time() - 20, "Another");

        $first = $this->databaseJob->getNextJob("Another");
        $second = $this->databaseJob->getNextJob("Another");

        $this->assertInstanceOf(TestJob::class, $first);
        $this->assertInstanceOf(TestJob::class, $second);
        $this->assertNotEquals($first->getJobId(), $second->getJobId(), "A claimed job must not be handed out again");
        // "first" was queued second but became available earlier, so it is claimed first
        $this->assertGreaterThan($second->getJobId(), $first->getJobId(), "Earliest available job comes first");
        $this->assertNull($this->databaseJob->getNextJob("Another"), "Both jobs are now reserved");

        $this->printResult("Claim each job once", true);
    }

    public function testGetNextJobIgnoresOtherQueuesAndFutureJobs()
    {
        $this->printHeader("Testing getNextJob respects queue and availability");

        $this->databaseJob->addFutureJob(new TestJob(), time() + 3600, "Another");
        $this->databaseJob->addJob(new TestJob(), "elsewhere");

        $this->assertNull($this->databaseJob->getNextJob("Another"));

        $this->printResult("Queue and availability respected", true);
    }

    public function testCompletedJobIsRemoved()
    {
        $this->printHeader("Testing markJobCompleted");

        $this->databaseJob->addJob(new TestJob(), "Another");
        $job = $this->databaseJob->getNextJob("Another");
        $this->databaseJob->markJobCompleted($job->getJobId());

        $this->assertSame(0, $this->countRows("tina4_jobs"));

        $this->printResult("Completed job removed", true);
    }

    public function testFailedJobIsMovedToFailedJobs()
    {
        $this->printHeader("Testing markJobFailed");

        $this->databaseJob->addJob(new TestJob(), "Another");
        $job = $this->databaseJob->getNextJob("Another");

        // A trace holding a closure is what made serialize($e) throw
        $exception = (function () { return new \RuntimeException("boom"); })();
        $this->databaseJob->markJobFailed(Tina4JobPayload::describe($exception), $job->getJobId(), Tina4JobPayload::encode($job), "Another");

        $this->assertSame(0, $this->countRows("tina4_jobs"));
        $failed = $this->rows("select connection, queue, exception from tina4_failed_jobs");
        $this->assertCount(1, $failed);
        $this->assertSame(Tina4DatabaseJob::class, $failed[0]["connection"]);
        $this->assertStringContainsString("RuntimeException: boom", $failed[0]["exception"]);

        $this->printResult("Failed job recorded", true);
    }

    public function testReleasedJobWaitsForItsRetryDelay()
    {
        $this->printHeader("Testing releaseJob");

        $this->databaseJob->addJob(new TestJob(), "Another");
        $job = $this->databaseJob->getNextJob("Another");
        $job->attempt();
        $this->databaseJob->releaseJob($job, 3600, "Another");

        $this->assertNull($this->databaseJob->getNextJob("Another"), "Released job waits out its delay");
        $row = $this->rows("select attempts, reserved_at from tina4_jobs")[0];
        $this->assertEquals(2, $row["attempts"]);
        $this->assertEquals(-1, $row["reserved_at"]);

        $this->printResult("Released job delayed", true);
    }

    public function testStaleJobsAreRetriedOrFailed()
    {
        $this->printHeader("Testing recoverStaleJobs");

        global $DBA;
        $longAgo = time() - 7200;
        // TestJob declares $attempts = 2, so it gets another try; a stdClass-free job without attempts would fail
        $DBA->exec(
            "insert into tina4_jobs (queue, payload, attempts, reserved_at, available_at, created_at) values (?, ?, 1, ?, ?, ?)",
            "Another", Tina4JobPayload::encode(new TestJob()), $longAgo, $longAgo, $longAgo
        );
        // Still running in this worker, so it must be left alone
        $DBA->exec(
            "insert into tina4_jobs (queue, payload, attempts, reserved_at, available_at, created_at) values (?, ?, 1, ?, ?, ?)",
            "Another", Tina4JobPayload::encode(new TestJob()), $longAgo, $longAgo, $longAgo
        );
        $DBA->commit();
        $runningId = (int)$this->rows("select max(id) as id from tina4_jobs")[0]["id"];

        $recovered = $this->databaseJob->recoverStaleJobs("Another", 3600, [$runningId]);

        $this->assertSame(1, $recovered);
        $rows = $this->rows("select id, attempts, reserved_at from tina4_jobs order by id");
        $this->assertEquals(-1, $rows[0]["reserved_at"], "Stale job is available again");
        $this->assertEquals(2, $rows[0]["attempts"], "Recovery counts as an attempt");
        $this->assertEquals($longAgo, $rows[1]["reserved_at"], "Job still running here is untouched");

        $this->printResult("Stale jobs recovered", true);
    }

    public function testUnreadablePayloadIsFailedNotLeftReserved()
    {
        $this->printHeader("Testing unreadable payloads");

        global $DBA;
        $DBA->exec(
            "insert into tina4_jobs (queue, payload, attempts, reserved_at, available_at, created_at) values (?, ?, 1, -1, ?, ?)",
            "Another", convert_uuencode('O:12:"MissingClass":0:{}'), time() - 10, time() - 10
        );
        $DBA->commit();

        $this->assertNull($this->databaseJob->getNextJob("Another"));
        $this->assertSame(0, $this->countRows("tina4_jobs"));
        $this->assertSame(1, $this->countRows("tina4_failed_jobs"));

        $this->printResult("Unreadable payload failed", true);
    }

    public function testRequeueFailedJobStartsOver()
    {
        $this->printHeader("Testing requeueFailedJob");

        $this->databaseJob->addJob(new TestJob(), "Another");
        $job = $this->databaseJob->getNextJob("Another");
        $job->attempt();
        $this->databaseJob->markJobFailed("boom", $job->getJobId(), Tina4JobPayload::encode($job), "Another");

        $uuid = $this->rows("select uuid from tina4_failed_jobs")[0]["uuid"];
        $this->databaseJob->requeueFailedJob($uuid);

        $this->assertSame(0, $this->countRows("tina4_failed_jobs"));
        $requeued = $this->databaseJob->getNextJob("Another");
        $this->assertInstanceOf(TestJob::class, $requeued, "Requeued job is available straight away");
        $this->assertSame(1, $requeued->getAttempt());

        $this->printResult("Failed job requeued", true);
    }

    private function countRows(string $table): int
    {
        return (int)$this->rows("select count(*) as n from {$table}")[0]["n"];
    }

    private function rows(string $sql): array
    {
        global $DBA;

        return $DBA->fetch($sql, 100)->asArray();
    }
}

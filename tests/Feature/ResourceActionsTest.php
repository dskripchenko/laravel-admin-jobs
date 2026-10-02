<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Tests\Feature;

use Dskripchenko\LaravelAdmin\Testing\Concerns\ActsAsAdmin;
use Dskripchenko\LaravelAdminJobs\Models\JobBatch;
use Dskripchenko\LaravelAdminJobs\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The panel's buttons go through core's `action` endpoint, which calls a
 * method of the resource named by the action. Those methods did not exist:
 * every retry, forget, cancel and retry-failed answered 501.
 */
final class ResourceActionsTest extends TestCase
{
    use ActsAsAdmin;

    private function failedJob(string $queue = 'default'): int
    {
        $uuid = (string) Str::uuid();

        return (int) DB::table('failed_jobs')->insertGetId([
            'uuid' => $uuid,
            'connection' => 'database',
            'queue' => $queue,
            'payload' => json_encode([
                'uuid' => $uuid,
                'displayName' => 'App\\Jobs\\SendInvoice',
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'maxTries' => null,
                'data' => ['commandName' => 'App\\Jobs\\SendInvoice', 'command' => 'O:8:"stdClass":0:{}'],
            ], JSON_THROW_ON_ERROR),
            'exception' => "RuntimeException: SMTP is down\n#0 /app/Jobs/SendInvoice.php(12)",
            'failed_at' => now(),
        ]);
    }

    /** @param  list<string>  $failedJobIds */
    private function batch(int $total, int $pending, array $failedJobIds = [], bool $finished = false): string
    {
        $id = (string) Str::uuid();
        DB::table('job_batches')->insert([
            'id' => $id,
            'name' => 'Nightly sync',
            'total_jobs' => $total,
            'pending_jobs' => $pending,
            'failed_jobs' => count($failedJobIds),
            'failed_job_ids' => json_encode($failedJobIds, JSON_THROW_ON_ERROR),
            'options' => serialize([]),
            'cancelled_at' => null,
            'created_at' => now()->subHour()->getTimestamp(),
            'finished_at' => $finished ? now()->getTimestamp() : null,
        ]);

        return $id;
    }

    public function test_retry_puts_the_job_back_on_its_queue(): void
    {
        $this->actingAsSuperAdmin();
        $id = $this->failedJob('high');

        $this->postJson('/api/admin/system-failed-jobs/action', ['key' => 'retry', 'ids' => [$id]])
            ->assertOk()
            ->assertJsonPath('payload.affected', 1);

        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(1, DB::table('jobs')->where('queue', 'high')->count());
    }

    public function test_bulk_retry_and_forget(): void
    {
        $this->actingAsSuperAdmin();
        $a = $this->failedJob();
        $b = $this->failedJob();
        $c = $this->failedJob();

        $this->postJson('/api/admin/system-failed-jobs/action', ['key' => 'retry_batch', 'ids' => [$a, $b]])
            ->assertOk()->assertJsonPath('payload.affected', 2);
        $this->postJson('/api/admin/system-failed-jobs/action', ['key' => 'forget_batch', 'ids' => [$c]])
            ->assertOk()->assertJsonPath('payload.affected', 1);

        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(2, DB::table('jobs')->count());
    }

    public function test_forget_deletes_without_requeueing(): void
    {
        $this->actingAsSuperAdmin();
        $id = $this->failedJob();

        $this->postJson('/api/admin/system-failed-jobs/action', ['key' => 'forget', 'ids' => [$id]])->assertOk();

        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_a_job_that_is_already_gone_is_a_refusal_not_an_error(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/api/admin/system-failed-jobs/action', ['key' => 'retry', 'ids' => [999]])
            ->assertStatus(422)
            ->assertJsonPath('payload.errorKey', 'action_failed');
    }

    public function test_cancel_a_running_batch(): void
    {
        $this->actingAsSuperAdmin();
        $id = $this->batch(10, 6);

        $this->postJson('/api/admin/system-job-batches/action', ['key' => 'cancel_batch', 'ids' => [$id]])
            ->assertOk()->assertJsonPath('payload.affected', 1);

        $this->assertSame('cancelled', JobBatch::query()->findOrFail($id)->status());
    }

    public function test_a_finished_batch_cannot_be_cancelled(): void
    {
        $this->actingAsSuperAdmin();
        $id = $this->batch(10, 0, finished: true);

        $this->postJson('/api/admin/system-job-batches/action', ['key' => 'cancel_batch', 'ids' => [$id]])
            ->assertStatus(422);
    }

    public function test_retry_the_failures_of_a_batch(): void
    {
        $this->actingAsSuperAdmin();
        $uuid = (string) DB::table('failed_jobs')->where('id', $this->failedJob())->value('uuid');
        $id = $this->batch(10, 1, [$uuid]);

        $this->postJson('/api/admin/system-job-batches/action', ['key' => 'retry_failed', 'ids' => [$id]])
            ->assertOk()->assertJsonPath('payload.affected', 1);

        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(1, DB::table('jobs')->count());
    }

    public function test_the_list_rows_carry_the_computed_columns(): void
    {
        $this->actingAsSuperAdmin();
        $this->failedJob();
        $this->batch(10, 3, ['x', 'y', 'z']);

        $job = $this->postJson('/api/admin/system-failed-jobs/search', [])->assertOk()->json('payload.data.0');
        $this->assertSame('App\\Jobs\\SendInvoice', $job['job_class']);
        $this->assertSame('RuntimeException', $job['exception_class']);
        $this->assertSame('SMTP is down', $job['exception_message']);

        $batch = $this->postJson('/api/admin/system-job-batches/search', [])->assertOk()->json('payload.data.0');
        $this->assertEquals(100, $batch['progress_pct']);
        $this->assertSame('finished_with_failures', $batch['state']);
    }
}

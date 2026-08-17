<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Tests\Feature;

use Dskripchenko\LaravelAdminJobs\Tests\TestCase;
use Dskripchenko\LaravelAdminJobs\Widgets\QueueDepthWidget;
use Illuminate\Support\Facades\DB;

/**
 * The widget the package was specified with and shipped without.
 *
 * Two lists — failed jobs and batches — describe the past. The number people
 * look at daily is the present one: how much is waiting right now.
 */
final class QueueDepthWidgetTest extends TestCase
{
    public function test_a_driver_that_cannot_be_counted_is_not_shown_as_zero(): void
    {
        // `sync` runs a job inside the request: there is no queue to measure.
        // A zero would read as "the queue is empty", which is a confident lie.
        config()->set('queue.default', 'sync');

        $data = (new QueueDepthWidget)->queues(['default'])->data();
        $labels = array_column($data['stats'], 'label');

        $this->assertNotContains('DEFAULT', $labels);
    }

    public function test_failed_jobs_are_counted_even_when_the_queue_cannot_be(): void
    {
        config()->set('queue.default', 'sync');

        DB::table('failed_jobs')->insert([
            'uuid' => 'a-b-c',
            'connection' => 'redis',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'boom',
            'failed_at' => now(),
        ]);

        $data = (new QueueDepthWidget)->data();
        $stats = collect($data['stats'])->keyBy('label');

        $this->assertSame(1, $stats->first()['value']);
    }

    public function test_the_database_driver_is_counted(): void
    {
        config()->set('queue.default', 'database');
        config()->set('queue.connections.database', [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ]);

        DB::table('jobs')->insert([
            ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => time(), 'created_at' => time()],
            ['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null, 'available_at' => time(), 'created_at' => time()],
        ]);

        $data = (new QueueDepthWidget)->queues(['default'])->data();
        $stats = collect($data['stats'])->keyBy('label');

        $this->assertSame(2, $stats['DEFAULT']['value']);
    }
}

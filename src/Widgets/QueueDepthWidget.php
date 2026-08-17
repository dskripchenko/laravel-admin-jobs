<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Widgets;

use Dskripchenko\LaravelAdmin\Widget\StatsOverviewWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * The depth of the queues, on the dashboard.
 *
 * The package was specified with this widget from the start and shipped without
 * it: it gave two lists — failed jobs and batches — that is, the past. The
 * number people actually look at daily is the present one, "how much is waiting
 * right now", and for it one had to go to the console.
 *
 * Where the number comes from depends on the driver. `database` is counted by
 * the query the worker itself uses — the rows of `jobs` that are not reserved.
 * Redis and the rest answer through `Queue::size()`. A driver that cannot count
 * (`sync`, `null`) is not shown at all rather than shown as a zero: a zero
 * reads as "the queue is empty", and the truth is "there is no queue here".
 */
class QueueDepthWidget extends StatsOverviewWidget
{
    /** @var list<string> */
    private array $queues = ['default'];

    private ?string $connection = null;

    public static function slug(): string
    {
        return 'admin.jobs.queue-depth';
    }

    /**
     * Which queues to show. The names are the host's own: the package cannot
     * know that the documents go to `render` and the letters to `notify`.
     *
     * @param  list<string>  $queues
     */
    public function queues(array $queues): static
    {
        $this->queues = array_values(array_filter($queues));

        return $this;
    }

    /** The connection; null means the application's default. */
    public function connection(?string $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        foreach ($this->queues as $queue) {
            $size = $this->queueSize($queue);

            if ($size === null) {
                continue;
            }

            $this->stat(mb_strtoupper($queue), $size);
        }

        $this->stat(mb_strtoupper(__('упало')), $this->failed());

        return parent::data();
    }

    /**
     * The number of jobs waiting in the queue, or null when the driver cannot
     * be asked.
     *
     * Not `size()`: the base widget already has one, and it means the width of
     * the tile on the dashboard. PHP said so with a fatal error, which is the
     * kind of collision a name like "size" invites.
     */
    private function queueSize(string $queue): ?int
    {
        $connection = Queue::connection($this->connection);
        $driver = config('queue.connections.'.($this->connection ?? (string) config('queue.default')).'.driver');

        // `sync` runs a job inside the request and `null` throws it away: there
        // is nothing to measure, and a zero would be a lie of the confident
        // kind.
        if (in_array($driver, ['sync', 'null'], true)) {
            return null;
        }

        try {
            return (int) $connection->size($queue);
        } catch (\Throwable) {
            // A driver may not implement size() — the widget is not a reason to
            // take the dashboard down over it.
            return null;
        }
    }

    private function failed(): int
    {
        try {
            return (int) DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}

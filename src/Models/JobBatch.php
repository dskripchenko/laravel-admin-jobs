<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * An Eloquent wrapper over Laravel's `job_batches` table (Bus::batch).
 *
 * @property string $id
 * @property string $name
 * @property int $total_jobs
 * @property int $pending_jobs
 * @property int $failed_jobs
 * @property string $failed_job_ids
 * @property string $options
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property-read int $processed_jobs
 * @property-read float $progress_pct
 * @property-read string $state  status() as an attribute
 */
final class JobBatch extends Model
{
    protected $table = 'job_batches';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_jobs' => 'integer',
        'pending_jobs' => 'integer',
        'failed_jobs' => 'integer',
    ];

    /**
     * Sent with every row: the list shows the progress and the state.
     *
     * @var list<string>
     */
    protected $appends = ['progress_pct', 'state'];

    /**
     * How many jobs have run, successfully or not.
     *
     * Laravel's counters need reading with care: a failed job is counted in
     * `failed_jobs` but stays in `pending_jobs`, and a successful retry takes
     * it out of `pending_jobs` and `failed_job_ids` while `failed_jobs` keeps
     * the historical count. What is still waiting to run is therefore
     * pending minus the failures that are still outstanding.
     */
    protected function processedJobs(): Attribute
    {
        return Attribute::get(
            fn (): int => max(0, (int) $this->total_jobs - $this->waitingJobs()),
        );
    }

    /**
     * The progress percentage (0..100). When total=0 it is 0.
     */
    protected function progressPct(): Attribute
    {
        return Attribute::get(function (): float {
            $total = (int) $this->total_jobs;
            if ($total === 0) {
                return 0.0;
            }

            return round(($this->processed_jobs / $total) * 100, 1);
        });
    }

    /**
     * The jobs that have not run yet.
     */
    public function waitingJobs(): int
    {
        return max(0, (int) $this->pending_jobs - $this->outstandingFailures());
    }

    /**
     * The failed jobs nobody has retried successfully yet — the ids Laravel
     * keeps in `failed_job_ids`. A row read without that column falls back
     * to the `failed_jobs` counter.
     */
    public function outstandingFailures(): int
    {
        $raw = $this->getAttributes()['failed_job_ids'] ?? null;
        if (! is_string($raw)) {
            return (int) $this->failed_jobs;
        }
        $ids = json_decode($raw, true);

        return is_array($ids) ? count($ids) : (int) $this->failed_jobs;
    }

    /**
     * The state ('running' | 'cancelled' | 'finished' | 'finished_with_failures').
     *
     * Not read from `finished_at` alone: Laravel stamps it only when the last
     * job succeeds, so a batch that allows failures and has run every job,
     * some of them failed, keeps a null `finished_at` until the failures are
     * retried. That batch is done, with failures to deal with. And once the
     * failures have been retried successfully it is simply finished, although
     * `failed_jobs` still counts them.
     */
    public function status(): string
    {
        if ($this->cancelled_at !== null) {
            return 'cancelled';
        }
        if ($this->finished_at === null && $this->waitingJobs() > 0) {
            return 'running';
        }

        return $this->outstandingFailures() > 0 ? 'finished_with_failures' : 'finished';
    }

    /**
     * status() as the `state` attribute, for the list and the view.
     */
    protected function state(): Attribute
    {
        return Attribute::get(fn (): string => $this->status());
    }
}

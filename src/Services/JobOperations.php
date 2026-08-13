<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Services;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

/**
 * The service encapsulating the operations over failed jobs and batches.
 *
 * It uses Laravel's artisan commands for the retries (the unserialize plus
 * dispatch logic there is involved) and database-level operations for the
 * forgets — the only way to be sure the invariants stay intact.
 */
final class JobOperations
{
    /**
     * Re-enqueue a single failed job by UUID. Internally it calls
     * `queue:retry {uuid}`, which assembles the payload and runs it again.
     */
    public function retryFailedJob(string $uuid): bool
    {
        $exitCode = Artisan::call('queue:retry', ['id' => [$uuid]]);

        return $exitCode === 0;
    }

    /**
     * A bulk retry. Returns the number of jobs successfully re-enqueued.
     *
     * @param  list<string>  $uuids
     */
    public function retryFailedJobs(array $uuids): int
    {
        if ($uuids === []) {
            return 0;
        }
        $exitCode = Artisan::call('queue:retry', ['id' => $uuids]);

        return $exitCode === 0 ? count($uuids) : 0;
    }

    /**
     * Delete the row from failed_jobs (without re-enqueueing it).
     */
    public function forgetFailedJob(string $uuid): bool
    {
        $deleted = DB::table('failed_jobs')->where('uuid', $uuid)->delete();

        return $deleted > 0;
    }

    /**
     * @param  list<string>  $uuids
     */
    public function forgetFailedJobs(array $uuids): int
    {
        if ($uuids === []) {
            return 0;
        }

        return DB::table('failed_jobs')->whereIn('uuid', $uuids)->delete();
    }

    /**
     * Cancel a batch (through Bus::findBatch plus cancel). The pending jobs
     * stay in the queue, but when they run they check `$batch->cancelled()` and
     * do not execute their logic.
     */
    public function cancelBatch(string $batchId): bool
    {
        $batch = Bus::findBatch($batchId);
        if (! $batch instanceof Batch) {
            return false;
        }

        $batch->cancel();

        return true;
    }

    /**
     * Re-enqueue every failed job of a batch through `queue:retry-batch {id}`.
     */
    public function retryBatchFailures(string $batchId): bool
    {
        $exitCode = Artisan::call('queue:retry-batch', ['id' => $batchId]);

        return $exitCode === 0;
    }
}

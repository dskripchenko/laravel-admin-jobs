<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Services;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Throwable;

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
        return $this->retry([$uuid])->count() === 1;
    }

    /**
     * A bulk retry. Returns the number of jobs actually re-enqueued.
     *
     * @param  list<string>  $uuids
     */
    public function retryFailedJobs(array $uuids): int
    {
        return $this->retry($uuids)->count();
    }

    /**
     * Re-enqueue failed jobs one by one through `queue:retry`, and say which
     * went back onto their queue and which could not.
     *
     * One at a time on purpose. `queue:retry` unserializes each job's command
     * to refresh its retry deadline, and a job whose model has been deleted
     * since (SerializesModels) throws from there — which aborts the whole
     * command at that job, leaving the rest of the list untouched. Retried
     * separately, that job is reported and the others still go.
     *
     * @param  list<string>  $uuids
     */
    public function retry(array $uuids): RetryResult
    {
        $failer = app('queue.failer');
        $retried = [];
        $failed = [];

        foreach (array_values(array_unique($uuids)) as $uuid) {
            if ($failer->find($uuid) === null) {
                $failed[$uuid] = __('Упавшая задача не найдена: её уже перезапустили или забыли.');

                continue;
            }

            try {
                Artisan::call('queue:retry', ['id' => [$uuid]]);
            } catch (Throwable $e) {
                $failed[$uuid] = $e instanceof ModelNotFoundException
                    ? __('Модель задачи больше не существует (:model).', ['model' => $e->getModel()])
                    : $e->getMessage();

                continue;
            }

            // The row existed and queue:retry did not throw: it pushed the
            // job and forgot the row.
            $retried[] = $uuid;
        }

        return new RetryResult($retried, $failed);
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
     * Re-enqueue every failed job of a batch. True when at least one went
     * back onto its queue.
     */
    public function retryBatchFailures(string $batchId): bool
    {
        return $this->retryBatch($batchId)->count() > 0;
    }

    /**
     * Re-enqueue every failed job of a batch, one by one (see retry()):
     * `queue:retry-batch` hands the whole list to `queue:retry` and stops at
     * the first job that cannot be unserialized.
     */
    public function retryBatch(string $batchId): RetryResult
    {
        $batch = Bus::findBatch($batchId);
        if (! $batch instanceof Batch) {
            return new RetryResult;
        }

        return $this->retry(array_values(array_map('strval', $batch->failedJobIds)));
    }
}

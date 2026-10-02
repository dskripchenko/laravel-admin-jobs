<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Resources;

use Dskripchenko\LaravelAdmin\Action\Button;
use Dskripchenko\LaravelAdmin\Filter\InputFilter;
use Dskripchenko\LaravelAdmin\Resource\ActionFailedException;
use Dskripchenko\LaravelAdmin\Resource\Resource;
use Dskripchenko\LaravelAdmin\Table\TableColumn;
use Dskripchenko\LaravelAdminJobs\Models\JobBatch;
use Dskripchenko\LaravelAdminJobs\Services\JobOperations;
use Illuminate\Database\Eloquent\Builder;

/**
 * A resource for browsing Bus::batch() batches.
 *
 * Permissions:
 *   - admin.system.jobs.batches.view
 *   - admin.system.jobs.batches.manage  (cancel + retry-failed)
 */
final class JobBatchResource extends Resource
{
    public static string $model = JobBatch::class;

    public static string $icon = 'layers';

    public static ?string $group = 'Системные';

    public static function slug(): string
    {
        return 'system-job-batches';
    }

    public static function permission(): string
    {
        return 'admin.system.jobs.batches';
    }

    public static function label(): string
    {
        return __('Пакеты задач');
    }

    public function columns(): array
    {
        return [
            // Every column carries a label: one made from the column name
            // stays English in every panel language.
            TableColumn::make('id')->label('ID')->copyable()->width('260px'),
            TableColumn::make('name')->label('Имя batch')->search()->sort(),
            TableColumn::make('state')->label('Статус')->asBadge([
                'running' => 'info',
                'finished' => 'success',
                'finished_with_failures' => 'danger',
                'cancelled' => 'default',
            ], self::statuses()),
            TableColumn::make('total_jobs')->label('Всего')->sort()->align('right'),
            TableColumn::make('pending_jobs')->label('В ожидании')->sort()->align('right'),
            TableColumn::make('failed_jobs')->label('Упало')->sort()->align('right'),
            TableColumn::make('progress_pct')
                ->label('Прогресс')
                ->align('right'),
            TableColumn::make('created_at')->label('Создано')->sort()->asDateTime(),
            TableColumn::make('finished_at')->label('Завершено')->sort()->asDateTime(),
            TableColumn::make('cancelled_at')->label('Отменено')->asDateTime()->defaultHidden(),
        ];
    }

    public function filters(): array
    {
        return [
            InputFilter::for('name')->label(__('Имя batch')),
        ];
    }

    public function actions(): array
    {
        return [
            // A Russian caption derives no name, so the names are explicit.
            Button::make('Отменить batch')->withName('cancel_batch')
                ->method('cancel')
                ->permission('admin.system.jobs.batches.manage')
                ->confirm(__('Отменить batch? Pending-jobs не будут выполнены.')),

            Button::make('Перезапустить упавшие')->withName('retry_failed')
                ->method('retryFailed')
                ->permission('admin.system.jobs.batches.manage')
                ->confirm(__('Перезапустить упавшие job\'ы внутри batch?')),
        ];
    }

    /**
     * The states of JobBatch::status() and their captions — source strings,
     * translated per request by core.
     *
     * @return array<string, string>
     */
    private static function statuses(): array
    {
        return [
            'running' => 'Выполняется',
            'finished' => 'Завершён',
            'finished_with_failures' => 'Завершён с ошибками',
            'cancelled' => 'Отменён',
        ];
    }

    /**
     * The action methods core's `action` endpoint calls with the selected
     * batch ids.
     *
     * @param  list<int|string>  $ids
     * @param  array<string, mixed>  $payload
     * @return array{message: string, affected: int}
     */
    public function cancel(array $ids, array $payload = []): array
    {
        $ops = app(JobOperations::class);
        $count = 0;
        foreach ($this->batches($ids) as $batch) {
            if ($batch->cancelled_at === null && $batch->status() === 'running' && $ops->cancelBatch((string) $batch->id)) {
                $count++;
            }
        }
        if ($count === 0) {
            throw new ActionFailedException(__('Отменить можно только выполняющийся batch.'));
        }

        return ['message' => __('Отменено пакетов: :count', ['count' => $count]), 'affected' => $count];
    }

    /**
     * @param  list<int|string>  $ids
     * @param  array<string, mixed>  $payload
     * @return array{message: string, affected: int}
     */
    public function retryFailed(array $ids, array $payload = []): array
    {
        $ops = app(JobOperations::class);
        $jobs = 0;
        foreach ($this->batches($ids) as $batch) {
            $outstanding = $batch->outstandingFailures();
            if ($outstanding > 0 && $ops->retryBatchFailures((string) $batch->id)) {
                $jobs += $outstanding;
            }
        }
        if ($jobs === 0) {
            throw new ActionFailedException(__('В этом batch нет упавших задач.'));
        }

        return ['message' => __('Перезапущено задач: :count', ['count' => $jobs]), 'affected' => $jobs];
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<JobBatch>
     */
    private function batches(array $ids): array
    {
        /** @var list<JobBatch> */
        return JobBatch::query()->whereKey($ids)->get()->all();
    }

    public function indexQuery(): Builder
    {
        return $this->modelQuery()->orderByDesc('created_at');
    }
}

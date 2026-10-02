<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Resources;

use Dskripchenko\LaravelAdmin\Action\Button;
use Dskripchenko\LaravelAdmin\Filter\InputFilter;
use Dskripchenko\LaravelAdmin\Resource\Resource;
use Dskripchenko\LaravelAdmin\Table\TableColumn;
use Dskripchenko\LaravelAdminJobs\Models\JobBatch;
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

    public function indexQuery(): Builder
    {
        return $this->modelQuery()->orderByDesc('created_at');
    }
}

<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Resources;

use Dskripchenko\LaravelAdmin\Action\BulkAction;
use Dskripchenko\LaravelAdmin\Action\Button;
use Dskripchenko\LaravelAdmin\Filter\DateRangeFilter;
use Dskripchenko\LaravelAdmin\Filter\InputFilter;
use Dskripchenko\LaravelAdmin\Filter\OptionsFilter;
use Dskripchenko\LaravelAdmin\Resource\Resource;
use Dskripchenko\LaravelAdmin\Table\TableColumn;
use Dskripchenko\LaravelAdminJobs\Models\FailedJob;
use Illuminate\Database\Eloquent\Builder;

/**
 * A resource for viewing, retrying and forgetting failed jobs.
 *
 * The form is read-only (there is no fields()) — editing a failed_job makes no
 * sense, only retrying or forgetting it does. Hence view plus actions, without
 * create/update.
 *
 * Permissions:
 *   - admin.system.jobs.failed.view   — list plus view
 *   - admin.system.jobs.failed.retry  — the retry action (single and bulk)
 *   - admin.system.jobs.failed.forget — the forget action (single and bulk)
 */
final class FailedJobResource extends Resource
{
    public static string $model = FailedJob::class;

    public static string $icon = 'alert-octagon';

    public static ?string $group = 'Системные';

    public static function slug(): string
    {
        return 'system-failed-jobs';
    }

    public static function permission(): string
    {
        return 'admin.system.jobs.failed';
    }

    public static function label(): string
    {
        return __('Упавшие задачи');
    }

    public function columns(): array
    {
        return [
            // Every column carries a label: one made from the column name
            // stays English in every panel language.
            TableColumn::make('id')->label('ID')->sort()->width('60px'),
            TableColumn::make('uuid')->label('UUID')->copyable()->width('260px'),
            TableColumn::make('connection')->label('Соединение')->sort()->search(),
            TableColumn::make('queue')->label('Очередь')->sort()->search()->asBadge([
                'default' => 'default',
                'high' => 'warning',
                'low' => 'info',
            ]),
            TableColumn::make('exception_class')
                ->label('Исключение')
                ->search()
                ->width('260px'),
            TableColumn::make('exception_message')
                ->label(__('Сообщение'))
                ->search(),
            TableColumn::make('failed_at')->label('Упало')->sort()->asDateTime(),
        ];
    }

    public function filters(): array
    {
        return [
            InputFilter::for('connection')->label('Соединение'),
            InputFilter::for('queue')->label('Очередь'),
            InputFilter::for('exception')
                ->label('Исключение (подстрока)'),
            DateRangeFilter::for('failed_at')->label(__('Период падений')),
            OptionsFilter::for('exception_class_group')
                ->label(__('Группа exception')),
        ];
    }

    public function actions(): array
    {
        return [
            // A Russian caption derives no name, so the names are explicit.
            Button::make('Перезапустить')->withName('retry')
                ->method('retry')
                ->permission('admin.system.jobs.failed.retry')
                ->confirm(__('Перезапустить упавший job?')),

            Button::make('Удалить')->withName('forget')
                ->method('forget')
                ->permission('admin.system.jobs.failed.forget')
                ->confirm(__('Удалить запись из failed_jobs? Job не будет перезапущен.')),

            BulkAction::make('Перезапустить выбранные')->withName('retry_batch')
                ->method('retryBatch')
                ->permission('admin.system.jobs.failed.retry')
                ->requiresAtLeast(1),

            BulkAction::make('Удалить выбранные')->withName('forget_batch')
                ->method('forgetBatch')
                ->permission('admin.system.jobs.failed.forget')
                ->requiresAtLeast(1),
        ];
    }

    public function searchableFields(): array
    {
        // The search goes by substring — the exception is stored as a blob
        // string, so we LIKE over it plus queue/connection.
        return ['exception', 'queue', 'connection', 'uuid'];
    }

    public function indexQuery(): Builder
    {
        return $this->modelQuery()->orderByDesc('failed_at');
    }
}

<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Resources;

use Dskripchenko\LaravelAdmin\Action\BulkAction;
use Dskripchenko\LaravelAdmin\Action\Button;
use Dskripchenko\LaravelAdmin\Field\Code;
use Dskripchenko\LaravelAdmin\Filter\DateRangeFilter;
use Dskripchenko\LaravelAdmin\Filter\InputFilter;
use Dskripchenko\LaravelAdmin\Infolist\FieldEntry;
use Dskripchenko\LaravelAdmin\Infolist\TextEntry;
use Dskripchenko\LaravelAdmin\Resource\ActionFailedException;
use Dskripchenko\LaravelAdmin\Resource\Resource;
use Dskripchenko\LaravelAdmin\Table\TableColumn;
use Dskripchenko\LaravelAdminJobs\Models\FailedJob;
use Dskripchenko\LaravelAdminJobs\Services\JobOperations;
use Dskripchenko\LaravelAdminJobs\Services\RetryResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * One record's name, for the panel's titles, confirmations and toasts
     * ("Create failed job"). A core without singularLabel() ignores it.
     */
    public static function singularLabel(): string
    {
        return __('упавшая задача');
    }

    public function columns(): array
    {
        return [
            // Every column carries a label: one made from the column name
            // stays English in every panel language.
            TableColumn::make('id')->label('ID')->sort()->width('60px'),
            TableColumn::make('job_class')->label('Задача')->width('260px'),
            TableColumn::make('uuid')->label('UUID')->copyable()->width('260px')->defaultHidden(),
            TableColumn::make('connection')->label('Соединение')->sort()->search(),
            TableColumn::make('queue')->label('Очередь')->sort()->search()->asBadge([
                'default' => 'default',
                'high' => 'warning',
                'low' => 'info',
            ]),
            // Computed from the exception text, so not searchable as columns:
            // the search goes over the whole text (see searchableFields()).
            TableColumn::make('exception_class')
                ->label('Исключение')
                ->width('260px'),
            TableColumn::make('exception_message')
                ->label('Сообщение'),
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

            Button::make('Забыть')->withName('forget')
                ->method('forget')
                ->permission('admin.system.jobs.failed.forget')
                ->confirm(__('Удалить запись из failed_jobs? Job не будет перезапущен.')),

            BulkAction::make('Перезапустить выбранные')->withName('retry_batch')
                ->method('retryBatch')
                ->permission('admin.system.jobs.failed.retry')
                ->requiresAtLeast(1),

            BulkAction::make('Забыть выбранные')->withName('forget_batch')
                ->method('forgetBatch')
                ->permission('admin.system.jobs.failed.forget')
                ->requiresAtLeast(1),
        ];
    }

    /**
     * The action methods core's `action` endpoint calls with the selected
     * row ids. The ids are the table's own; the queue commands want uuids.
     *
     * @param  list<int|string>  $ids
     * @param  array<string, mixed>  $payload
     * @return array{message: string, affected: int}
     */
    public function retry(array $ids, array $payload = []): array
    {
        return $this->retryBatch($ids, $payload);
    }

    /**
     * @param  list<int|string>  $ids
     * @param  array<string, mixed>  $payload
     * @return array{message: string, affected: int}
     */
    public function retryBatch(array $ids, array $payload = []): array
    {
        return self::retryOutcome(app(JobOperations::class)->retry($this->uuids($ids)));
    }

    /**
     * @param  list<int|string>  $ids
     * @param  array<string, mixed>  $payload
     * @return array{message: string, affected: int}
     */
    public function forget(array $ids, array $payload = []): array
    {
        return $this->forgetBatch($ids, $payload);
    }

    /**
     * @param  list<int|string>  $ids
     * @param  array<string, mixed>  $payload
     * @return array{message: string, affected: int}
     */
    public function forgetBatch(array $ids, array $payload = []): array
    {
        $count = app(JobOperations::class)->forgetFailedJobs($this->uuids($ids));

        return ['message' => __('Забыто задач: :count', ['count' => $count]), 'affected' => $count];
    }

    /**
     * What a retry reports: how many went back onto their queues and, when
     * some could not, why. Nothing retried at all is a refusal.
     *
     * @return array{message: string, affected: int}
     */
    public static function retryOutcome(RetryResult $result): array
    {
        $reasons = implode('; ', $result->reasons());
        if ($result->count() === 0) {
            throw new ActionFailedException($reasons !== '' ? $reasons : __('Нечего перезапускать.'));
        }

        $message = __('Перезапущено задач: :count', ['count' => $result->count()]);
        if ($result->failed !== []) {
            $message .= '. '.__('Не перезапущено: :count (:reasons)', ['count' => count($result->failed), 'reasons' => $reasons]);
        }

        return ['message' => $message, 'affected' => $result->count()];
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<string>
     */
    private function uuids(array $ids): array
    {
        $uuids = FailedJob::query()->whereKey($ids)->pluck('uuid')->map(static fn (mixed $u): string => (string) $u)->values()->all();
        if ($uuids === []) {
            // Someone else retried or forgot them a moment ago.
            throw new ActionFailedException(__('Упавшие задачи не найдены: их уже перезапустили или забыли.'));
        }

        return $uuids;
    }

    /**
     * The job, not the exception: the default title would be the first
     * searchable field — the whole exception text with its stack trace.
     */
    public function recordTitle(Model $row): string
    {
        return $row instanceof FailedJob ? class_basename($row->jobName()) : parent::recordTitle($row);
    }

    public function recordSubtitle(Model $row): ?string
    {
        return $row instanceof FailedJob ? $row->exception_class : parent::recordSubtitle($row);
    }

    /**
     * The view page: what failed and where, then the trace and the payload
     * as code blocks rather than one run-on paragraph.
     */
    public function infolist(): array
    {
        return [
            TextEntry::make('job_class')->label('Задача')->copyable(),
            TextEntry::make('queue')->label('Очередь'),
            TextEntry::make('connection')->label('Соединение'),
            TextEntry::make('uuid')->label('UUID')->copyable(),
            TextEntry::make('failed_at')->label('Упало')->preset('datetime', self::failedAtColumn()['meta'] ?? []),
            TextEntry::make('exception_class')->label('Исключение'),
            TextEntry::make('exception_message')->label('Сообщение'),
            FieldEntry::fromField(Code::make('exception')->title('Трассировка')->language('text')),
            FieldEntry::fromField(Code::make('payload')->title('Payload')->language('json')),
        ];
    }

    /**
     * The list's `failed_at` column as serialized, so the view formats the
     * time the way the list does.
     *
     * @return array<string, mixed>
     */
    private static function failedAtColumn(): array
    {
        foreach ((new self)->columns() as $column) {
            $array = $column->toArray();
            if (($array['name'] ?? null) === 'failed_at') {
                return $array;
            }
        }

        return [];
    }

    /**
     * The record for the view: the payload pretty-printed, so its code block
     * reads as JSON rather than one escaped line.
     *
     * @return array<string, mixed>
     */
    public function transformRecord(Model $record): array
    {
        $data = parent::transformRecord($record);
        $payload = json_decode((string) ($data['payload'] ?? ''), true);
        if (is_array($payload)) {
            $data['payload'] = (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $data;
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

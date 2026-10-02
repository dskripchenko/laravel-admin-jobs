<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Tests\Feature;

use Dskripchenko\LaravelAdmin\Action\Action;
use Dskripchenko\LaravelAdminJobs\Resources\FailedJobResource;
use Dskripchenko\LaravelAdminJobs\Resources\JobBatchResource;
use Dskripchenko\LaravelAdminJobs\Tests\TestCase;
use Dskripchenko\LaravelAdminJobs\Widgets\QueueDepthWidget;

/**
 * The sections, columns, filters and actions read in the panel's language.
 * They used to be English source strings ("Failed jobs", "Retry") or labels
 * made from column names ("Failed at"), English in a Russian panel too.
 */
final class CaptionsLocaleTest extends TestCase
{
    /**
     * @param  class-string<FailedJobResource|JobBatchResource>  $resource
     * @return array<string, string>
     */
    private function columns(string $resource): array
    {
        $out = [];
        foreach ((new $resource)->columns() as $column) {
            $arr = $column->toArray();
            $out[(string) $arr['name']] = (string) $arr['label'];
        }

        return $out;
    }

    public function test_russian_panel(): void
    {
        app()->setLocale('ru');

        $this->assertSame('Упавшие задачи', FailedJobResource::label());
        $this->assertSame('Пакеты задач', JobBatchResource::label());
        $this->assertSame('Очереди', (new QueueDepthWidget)->toArray()['title']);

        foreach ([FailedJobResource::class, JobBatchResource::class] as $resource) {
            foreach ($this->columns($resource) as $name => $label) {
                $this->assertMatchesRegularExpression('/\p{Cyrillic}|^(ID|UUID)$/u', $label, "{$resource}::{$name}");
            }
        }
    }

    public function test_english_panel(): void
    {
        app()->setLocale('en');

        $this->assertSame('Failed jobs', FailedJobResource::label());
        $this->assertSame('Batch jobs', JobBatchResource::label());
        $this->assertSame('Queue', $this->columns(FailedJobResource::class)['queue']);
        $this->assertSame('Progress', $this->columns(JobBatchResource::class)['progress_pct']);
    }

    public function test_actions_keep_their_names(): void
    {
        $names = static fn (array $actions): array => array_map(static fn (Action $a): string => $a->name(), $actions);

        $this->assertSame(['retry', 'forget', 'retry_batch', 'forget_batch'], $names((new FailedJobResource)->actions()));
        $this->assertSame(['cancel_batch', 'retry_failed'], $names((new JobBatchResource)->actions()));
    }
}

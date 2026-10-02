<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Tests\Unit;

use Dskripchenko\LaravelAdminJobs\Models\FailedJob;
use Dskripchenko\LaravelAdminJobs\Resources\FailedJobResource;
use Dskripchenko\LaravelAdminJobs\Tests\TestCase;

final class FailedJobResourceTest extends TestCase
{
    private function row(): FailedJob
    {
        return new FailedJob([
            'uuid' => 'a', 'connection' => 'database', 'queue' => 'default',
            'payload' => (string) json_encode(['displayName' => 'App\\Jobs\\SendInvoice']),
            'exception' => "RuntimeException: SMTP is down\n#0 /app/Jobs/SendInvoice.php(12)\n#1 {main}",
        ]);
    }

    public function test_the_title_is_the_job_not_the_stack_trace(): void
    {
        $resource = new FailedJobResource;

        $this->assertSame('SendInvoice', $resource->recordTitle($this->row()));
        $this->assertSame('RuntimeException', $resource->recordSubtitle($this->row()));
    }

    public function test_the_view_shows_the_trace_and_the_payload(): void
    {
        $names = array_map(static fn ($entry): string => $entry->name(), (new FailedJobResource)->infolist());

        foreach (['job_class', 'queue', 'uuid', 'failed_at', 'exception_class', 'exception_message', 'exception', 'payload'] as $name) {
            $this->assertContains($name, $names);
        }
    }

    public function test_every_filter_targets_a_real_column(): void
    {
        foreach ((new FailedJobResource)->filters() as $filter) {
            $this->assertContains($filter->field(), ['connection', 'queue', 'exception', 'failed_at']);
        }
    }

    public function test_forget_reads_forget_in_english(): void
    {
        app()->setLocale('en');
        $labels = array_map(static fn ($action): string => (string) ($action->toArray()['label'] ?? $action->toArray()['title'] ?? ''), (new FailedJobResource)->actions());

        $this->assertContains('Forget', $labels);
        $this->assertContains('Forget selected', $labels);
    }
}

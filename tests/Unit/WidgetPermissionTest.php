<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Tests\Unit;

use Dskripchenko\LaravelAdminJobs\Tests\TestCase;
use Dskripchenko\LaravelAdminJobs\Widgets\QueueDepthWidget;

final class WidgetPermissionTest extends TestCase
{
    public function test_the_widget_requires_the_view_permission_by_default(): void
    {
        $this->assertSame('admin.system.jobs.failed.view', (new QueueDepthWidget)->getPermission());
    }
}

<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs;

use Dskripchenko\LaravelAdmin\Admin;
use Dskripchenko\LaravelAdmin\Permission\ItemPermission;
use Dskripchenko\LaravelAdmin\Plugin\AdminPlugin;
use Dskripchenko\LaravelAdminJobs\Resources\FailedJobResource;
use Dskripchenko\LaravelAdminJobs\Resources\JobBatchResource;
use Dskripchenko\LaravelAdminJobs\Widgets\QueueDepthWidget;

/**
 * An admin plugin registering the failed-jobs and batches resources together
 * with their permission groups.
 *
 * A host project wires it in through
 * `Admin::plugins([AdminJobsPlugin::class])` or puts it into `plugins[]` of
 * `config/admin.php`.
 */
final class AdminJobsPlugin implements AdminPlugin
{
    public function name(): string
    {
        return 'jobs';
    }

    public function version(): string
    {
        return '0.1.0';
    }

    public function register(): void
    {
        // A no-op — the service bindings are registered by the service provider.
    }

    public function boot(Admin $admin): void
    {
        $admin->resources([
            FailedJobResource::class,
            JobBatchResource::class,
        ]);

        // The widget is registered, not placed: which queues a host has and
        // whether the depth belongs on its dashboard is the host's call. It
        // adds it to a dashboard through `QueueDepthWidget::make()->queues([...])`.
        $admin->widgets([QueueDepthWidget::class]);

        $admin->permissions(
            ItemPermission::group('Системные')
                ->addPermission('admin.system.jobs.failed.view', 'Failed jobs: просмотр')
                ->addPermission('admin.system.jobs.failed.retry', 'Failed jobs: retry')
                ->addPermission('admin.system.jobs.failed.forget', 'Failed jobs: forget')
                ->addPermission('admin.system.jobs.batches.view', 'Batches: просмотр')
                ->addPermission('admin.system.jobs.batches.manage', 'Batches: cancel/retry'),
        );
    }
}

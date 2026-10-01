<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Tests\Feature;

use Dskripchenko\LaravelAdmin\Admin;
use Dskripchenko\LaravelAdminJobs\AdminJobsPlugin;
use Dskripchenko\LaravelAdminJobs\Resources\FailedJobResource;
use Dskripchenko\LaravelAdminJobs\Resources\JobBatchResource;
use Dskripchenko\LaravelAdminJobs\Tests\TestCase;

final class PluginRegistrationTest extends TestCase
{
    public function test_plugin_class_is_added_to_admin_plugins_config(): void
    {
        $plugins = (array) config('admin.plugins', []);
        $this->assertContains(AdminJobsPlugin::class, $plugins);
    }

    public function test_plugin_registers_resources(): void
    {
        /** @var Admin $admin */
        $admin = app(Admin::class);
        $resources = $admin->getResources();

        $this->assertContains(FailedJobResource::class, $resources);
        $this->assertContains(JobBatchResource::class, $resources);
    }

    public function test_plugin_registers_permissions(): void
    {
        /** @var Admin $admin */
        $admin = app(Admin::class);
        $registry = $admin->getPermissionRegistry();

        foreach (
            [
                'admin.system.jobs.failed.view',
                'admin.system.jobs.failed.retry',
                'admin.system.jobs.failed.forget',
                'admin.system.jobs.batches.view',
                'admin.system.jobs.batches.manage',
            ] as $key
        ) {
            $this->assertTrue($registry->knows($key), "permission $key not registered");
        }
    }

    public function test_version_comes_from_composer_instead_of_a_literal(): void
    {
        $version = (new AdminJobsPlugin)->version();

        $this->assertNotSame('', $version);
        $this->assertNotSame('0.1.0', $version);
    }

    public function test_english_translations_are_loaded(): void
    {
        app()->setLocale('en');

        $this->assertSame('Queues', __('Очереди'));
        $this->assertSame('System', __('Системные'));
    }

    public function test_every_russian_string_in_src_has_an_english_translation(): void
    {
        /** @var array<string, string> $en */
        $en = json_decode((string) file_get_contents(__DIR__.'/../../resources/lang/en.json'), true, flags: JSON_THROW_ON_ERROR);

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/../../src'));
        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            preg_match_all("/'((?:[^'\\\\]|\\\\.)*\\p{Cyrillic}(?:[^'\\\\]|\\\\.)*)'/u", (string) file_get_contents($file->getPathname()), $m);
            foreach ($m[1] as $key) {
                $key = stripslashes($key);
                $this->assertArrayHasKey($key, $en, $file->getFilename().': '.$key);
            }
        }
    }
}

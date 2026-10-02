<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

final class SendInvoice implements ShouldQueue
{
    use SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function handle(): void {}
}

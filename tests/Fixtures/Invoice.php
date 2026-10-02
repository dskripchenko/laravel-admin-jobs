<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class Invoice extends Model
{
    protected $table = 'test_invoices';

    protected $guarded = [];
}

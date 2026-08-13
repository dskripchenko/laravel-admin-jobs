<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | The queues to monitor
    |--------------------------------------------------------------------------
    | The list of queue names for QueueDepthWidget. They must exist in the host
    | project's queue driver. Empty means autodetection through
    | `Queue::getNames()` (when the driver supports it).
    */

    'queues_to_monitor' => ['default'],

    /*
    |--------------------------------------------------------------------------
    | Whether to show the queue's backlog (the queued jobs)
    |--------------------------------------------------------------------------
    | true only for the DB queue driver (the `jobs` table). It does not work
    | for Redis/SQS (those drivers provide no per-job listing). For the DB
    | driver it gives a `QueuedJobResource` with the list of pending jobs.
    */

    'show_queued' => env('ADMIN_JOBS_SHOW_QUEUED', false),

    /*
    |--------------------------------------------------------------------------
    | Notification on failed job
    |--------------------------------------------------------------------------
    | A listener on Laravel's standard JobFailed event (see AdminJobsPlugin).
    | It sends a notification to the admin notification centre when a failed job
    | shows up.
    */

    'notification' => [
        'on_failed' => env('ADMIN_JOBS_NOTIFY_ON_FAILED', true),
        'rate_limit_per_minute' => 1,
        'group_by_fingerprint' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Truncating the payload in the collapsed view
    |--------------------------------------------------------------------------
    | The length in characters for rendering a preview of the payload or the
    | exception in the table. The full payload is available through the viewer
    | on the view page.
    */

    'payload_truncate' => 5000,
];

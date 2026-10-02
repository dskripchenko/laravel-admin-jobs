---
title: Usage
locale: en
status: stable
---

# Usage

Permissions:

- `admin.system.jobs.failed.view` — the failed jobs list and view
- `admin.system.jobs.failed.retry` — retry (one or the selected)
- `admin.system.jobs.failed.forget` — forget (one or the selected): delete the record without retrying
- `admin.system.jobs.batches.view` — the batches list and view
- `admin.system.jobs.batches.manage` — cancel a running batch, retry a batch's failed jobs

The actions call `queue:retry` / `queue:retry-batch` and `Bus::findBatch()->cancel()`:
a retried job goes back to its own connection and queue, so a worker has to
listen to that queue.

A batch's state follows Laravel's counters rather than `finished_at` alone:
a batch that allows failures and has run every job is "finished with
failures" while its failed jobs wait for a retry (Laravel stamps
`finished_at` only when the last job succeeds), and "finished" once they
have been retried successfully.

Configure visible queues / connections:

```php
// config/admin-jobs.php
'connections' => ['redis', 'database'],
'queues' => ['default', 'high', 'low'],
```


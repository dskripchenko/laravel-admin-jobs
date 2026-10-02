---
title: Getting Started
locale: en
status: stable
---

# Getting Started

`dskripchenko/laravel-admin-jobs` is a sister-pack of `dskripchenko/laravel-admin`.
Install once — it auto-registers and surfaces in your admin.

## Install

```bash
composer require dskripchenko/laravel-admin-jobs
php artisan migrate
```

## Configure

```bash
php artisan vendor:publish --tag=admin-jobs-config
```

Edit `config/jobs.php`.


## What it adds

Three resources rendered as tables:

- **Failed jobs** `/admin/r/system-failed-jobs` — the `failed_jobs` table: the
  job, queue, exception class and message per row; the view shows the stack
  trace and the payload. Actions: retry / forget, one or the selected.
- **Batches** `/admin/r/system-job-batches` — the `job_batches` table with the
  progress and the state of each batch. Actions: cancel, retry the failed jobs.
- **Queue depth** dashboard — pending jobs per queue.

Standard Laravel queue worker is required (`queue:work`).

## See also

- [Usage](usage.md)
- [Glossary](https://github.com/dskripchenko/laravel-admin/blob/main/docs/en/glossary.md)

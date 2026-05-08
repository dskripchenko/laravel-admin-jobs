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
php artisan vendor:publish --tag=jobs-config
```

Edit `config/jobs.php`.


## What it adds

Three resources rendered as tables:

- **Failed jobs** `/admin/r/failed-jobs` — `failed_jobs` table viewer.
  Action: retry / forget.
- **Batches** `/admin/r/job-batches` — `job_batches` table viewer.
- **Queue depth** dashboard — pending jobs per queue.

Standard Laravel queue worker is required (`queue:work`).

## See also

- [Usage](usage.md)
- [Glossary](https://github.com/dskripchenko/laravel-admin/blob/main/docs/en/glossary.md)

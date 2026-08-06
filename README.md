# dskripchenko/laravel-admin-jobs

> 🌐 **English** · [Русский](docs/ru/README.md) · [Deutsch](docs/de/README.md) · [中文](docs/zh/README.md)

Viewer for Laravel queues: failed jobs, batches, queue depth. Lightweight, no dashboard server.

A sister-pack for [`dskripchenko/laravel-admin`](https://github.com/dskripchenko/laravel-admin).

[![Packagist](https://img.shields.io/packagist/v/dskripchenko/laravel-admin-jobs)](https://packagist.org/packages/dskripchenko/laravel-admin-jobs)
[![License](https://img.shields.io/packagist/l/dskripchenko/laravel-admin-jobs)](LICENSE)

## Install

```bash
composer require dskripchenko/laravel-admin-jobs
php artisan migrate
```

The plugin auto-registers via Laravel package discovery. To publish the
config:

```bash
php artisan vendor:publish --tag=jobs-config
```

## Documentation

- [Getting started](docs/en/getting-started.md)
- [Usage](docs/en/usage.md)

## License

[MIT](LICENSE) © Denis Skripchenko

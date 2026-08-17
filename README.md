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

## Глубина очереди на дашборде

Два списка — упавшие задачи и батчи — описывают прошлое. Число, на которое
смотрят каждый день, другое: сколько ждёт прямо сейчас. Виджет
`QueueDepthWidget` показывает его рядом с количеством упавших.

```php
use Dskripchenko\LaravelAdminJobs\Widgets\QueueDepthWidget;

(new QueueDepthWidget)
    ->title('Очереди')->size(4)
    ->queues(['default', 'render', 'notify']);
```

Имена очередей — ваши: пакет не может знать, что документы идут в `render`, а
письма в `notify`. Драйвер, который посчитать нельзя (`sync`, `null`), не
показывается вовсе — ноль читался бы как «очередь пуста», а правда в том, что
очереди здесь нет.

## Documentation

- [Getting started](docs/en/getting-started.md)
- [Usage](docs/en/usage.md)

## License

[MIT](LICENSE) © Denis Skripchenko

# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Entries for releases published before this file existed were reconstructed from
the tagged commit history.

## [v1.4.0] - 2026-08-17

### Added
- **Queue-depth widget.** The pack was specified with it and shipped without
  one, so it showed only the past — failed jobs and batches — while the number
  people look at daily is "how much is waiting right now", which meant going to
  the console. Configurable queues and connection; a driver that cannot be
  counted (`sync`, `null`) is left out rather than shown as a zero, because a
  zero reads as "the queue is empty" when the truth is "there is no queue here".

### Changed
- Requires `dskripchenko/laravel-admin` ^1.30 — the release where widgets
  registered by a plugin are actually rendered. On anything older the widget is
  registered and never appears.

## [v1.3.0] - 2026-07-20

### Changed
- Supported versions moved to the canonical matrix: PHP 8.2-8.5 with Laravel 11, 12 and 13.

### Added
- GitHub Actions pipeline covering the whole support matrix.
- Documentation in German, Russian and Chinese alongside the English default.

## [v1.2.0] - 2026-05-01

### Changed
- Version aligned with the admin core release line. No functional changes.

## [v1.0.0] - 2026-05-01

### Added
- First standalone release, extracted from the laravel-admin monorepo.
- Packagist metadata: description, keywords, authors and support links.

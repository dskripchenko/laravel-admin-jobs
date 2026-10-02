# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Entries for releases published before this file existed were reconstructed from
the tagged commit history.

## [Unreleased]

### Fixed
- The permission group and its labels were registered as `__()` results,
  translated once at boot: the role matrix showed them in the boot locale
  whatever the request's language, and apart from the "Системные" group of the
  other packs when those register the source string. The group and the labels
  are now passed as source strings, which core translates per request.
- The retry, forget and cancel/retry permissions were labelled in English
  with no Russian source string, so a Russian role matrix showed them in
  English. They now have Russian source strings with English translations.

### Changed
- Requires `dskripchenko/laravel-admin` ^1.33, the first core to translate
  permission groups and labels per request.

## [1.4.2] — 2026-10-02

### Changed
- `QueueDepthWidget` requires `admin.system.jobs.failed.view` by default. Since laravel-admin 1.34 enforces
  widget permissions and serves plugin widgets on dashboards, the widget would
  otherwise be visible to everyone who can open a dashboard. Override it with
  `->permission()`.

## [1.4.1] — 2026-10-01

### Added
- English translations of the pack's user-facing strings (`resources/lang/en.json`),
  loaded as JSON translations; the Russian source text stays the key.
- Weekly scheduled CI run, so the support matrix is re-checked against new
  upstream releases even without commits.

### Fixed
- `AdminJobsPlugin::version()` reports the installed package version instead of
  a hardcoded `0.1.0` (falls back to `dev` when it cannot be resolved).
- The config publish tag in the docs is `admin-jobs-config`, matching the one the
  service provider registers (the docs said `jobs-config`).

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

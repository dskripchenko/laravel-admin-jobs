# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Entries for releases published before this file existed were reconstructed from
the tagged commit history.

## [1.5.1] — 2026-10-03

### Added

- `singularLabel()` on the pack's resources ("job batch", "failed job", with Russian source
  strings and English translations), so a laravel-admin core that supports
  it titles their pages "Create job batch" instead of gluing the plural
  label. An older core ignores the method; the core constraint is unchanged.

## [1.5.0] — 2026-10-02

### Fixed
- Every action of the failed jobs and batches sections answered 501 "Method
  `retry` not found on resource": the buttons go through core's `action`
  endpoint, which calls a method of the resource, and the resources had none.
  `retry`, `forget`, `retryBatch`, `forgetBatch` (failed jobs) and `cancel`,
  `retryFailed` (batches) now run through `JobOperations` and answer with the
  number of jobs or batches affected; a job somebody has already retried or
  forgotten, a batch that is not running or has no failures is a refusal
  (422), not an error.
- A retry stopped at the first failed job whose model had been deleted since
  it failed (`SerializesModels`): `queue:retry` unserializes each command, the
  ModelNotFoundException aborted the command, a single retry answered 500 and
  a bulk retry left the rest of the list untouched while reporting all of them
  retried. `JobOperations::retry()` (new, with `retryBatch()`) retries the jobs
  one by one and returns a `RetryResult` — the uuids pushed back and, for the
  others, the reason ("the job's model no longer exists"). The actions report
  a partial result, or refuse when nothing could be retried; the `retry-batch`
  and batch `retry-failed` routes add `count` and `failed` (uuid => reason) to
  their payload, `retry` adds `reason`. `retryFailedJobs()` returns the jobs
  actually retried, not the number asked for.
- The failed jobs list showed "—" in the Exception and Message columns: the
  accessors were not serialized. They are appended now, with a new `job_class`
  column — the job itself, which the list did not show at all.
- The failed job's page used the whole exception text, stack trace included,
  as its title. The title is the job's class, the subtitle the exception's,
  and the page lists the job, queue, connection, uuid, time and exception,
  then the stack trace and the payload (pretty-printed) as code blocks; the
  time is formatted as in the list.
- The Message column kept the " in /path/File.php:24" Laravel appends to the
  first line of the stored exception; the file and line are left to the trace.
- The "Exception group" filter had no options and filtered on a column that
  does not exist (an SQL error on MySQL and PostgreSQL, an empty list on
  SQLite). It is removed; "Exception (substring)" searches the exception text.
- The batches list showed "—" in the Progress column (not serialized), and a
  batch's state did not follow Laravel's counters: a batch that allows
  failures and has run every job keeps a null `finished_at` while its failed
  jobs wait (it read "running" forever), and a batch whose failures were
  retried successfully read "finished with failures" (`failed_jobs` keeps the
  historical count). The state now counts the outstanding failures
  (`failed_job_ids`); the progress counts the jobs that have run, failed ones
  included. Both are serialized (`progress_pct`, `state`), and the list has a
  Status column.
- "Forget" read "Delete" in English next to core's own Delete, while the bulk
  action read "Forget selected": the source string was core's "Удалить".

## [1.4.4] — 2026-10-02

### Fixed
- The failed jobs and batches sections were English in a Russian panel: their
  titles ("Failed jobs", "Batch jobs"), several column and filter labels
  ("Exception", "Connection", "Queue", "Progress") and the actions ("Retry",
  "Forget", "Cancel batch"…) were English source strings, and the other
  columns had headers made from their names ("Failed at", "Total jobs"). They
  are Russian source strings now, translated per request; the actions keep
  their names (`retry`, `forget`, `retry_batch`, `forget_batch`,
  `cancel_batch`, `retry_failed`). The queue widget passes its title as a
  source string, so core translates it in the reader's locale.

## [1.4.3] — 2026-10-02

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

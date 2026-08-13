<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * An Eloquent wrapper over Laravel's standard `failed_jobs` table.
 *
 * It has no migration of its own — it uses the existing table.
 *
 * @property int $id
 * @property string $uuid
 * @property string $connection
 * @property string $queue
 * @property string $payload
 * @property string $exception
 * @property \Illuminate\Support\Carbon $failed_at
 * @property-read string $exception_class
 * @property-read string $exception_message
 * @property-read string $exception_fingerprint  A hash of the exception's first
 *                                               lines, for grouping identical
 *                                               failures.
 */
final class FailedJob extends Model
{
    protected $table = 'failed_jobs';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'failed_at' => 'datetime',
    ];

    /**
     * The exception's class name (the first line of the exception up to ': ').
     */
    protected function exceptionClass(): Attribute
    {
        return Attribute::get(function (): string {
            $first = strtok((string) $this->exception, "\n");
            if ($first === false) {
                return 'Exception';
            }
            $colon = strpos($first, ': ');

            return $colon === false ? $first : substr($first, 0, $colon);
        });
    }

    /**
     * The exception's message (without the stack trace).
     */
    protected function exceptionMessage(): Attribute
    {
        return Attribute::get(function (): string {
            $first = strtok((string) $this->exception, "\n");
            if ($first === false) {
                return '';
            }
            $colon = strpos($first, ': ');

            return $colon === false ? '' : substr($first, $colon + 2);
        });
    }

    /**
     * A hash of the exception's first N lines — for grouping identical failures
     * (rate-limiting the notifications, find-similar in the UI).
     */
    protected function exceptionFingerprint(): Attribute
    {
        return Attribute::get(function (): string {
            $lines = preg_split('/\r?\n/', (string) $this->exception, 6);
            $head = implode("\n", array_slice((array) $lines, 0, 5));

            return substr(hash('xxh64', $head), 0, 16);
        });
    }

    /**
     * The decoded payload (without pulling the whole blob into a property).
     *
     * @return array<string, mixed>
     */
    public function decodedPayload(): array
    {
        $decoded = json_decode((string) $this->payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * The job's own class name (from payload['displayName']).
     */
    public function jobName(): string
    {
        $p = $this->decodedPayload();

        return is_string($p['displayName'] ?? null) ? $p['displayName'] : 'Unknown';
    }
}

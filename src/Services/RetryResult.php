<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Services;

/**
 * What a retry did: the failed jobs pushed back onto their queues, and the
 * ones that could not be, each with the reason.
 */
final class RetryResult
{
    /**
     * @param  list<string>  $retried  uuids
     * @param  array<string, string>  $failed  uuid => reason
     */
    public function __construct(
        public readonly array $retried = [],
        public readonly array $failed = [],
    ) {}

    public function count(): int
    {
        return count($this->retried);
    }

    /**
     * The distinct reasons, for a message.
     *
     * @return list<string>
     */
    public function reasons(): array
    {
        return array_values(array_unique($this->failed));
    }
}

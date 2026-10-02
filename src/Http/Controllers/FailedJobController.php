<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Http\Controllers;

use Dskripchenko\LaravelAdminJobs\Services\JobOperations;
use Dskripchenko\LaravelApi\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The custom routes for retrying and forgetting failed jobs.
 *
 * The endpoints are registered in AdminJobsServiceProvider::boot() and are
 * covered by the `AdminAccess::class.':admin.system.jobs.failed.{action}'`
 * middleware.
 */
final class FailedJobController extends ApiController
{
    public function __construct(private readonly JobOperations $ops) {}

    /**
     * @input string $uuid The failed job's UUID.
     *
     * @output object $payload
     * @output bool $payload.success
     * @output string $payload.reason Why the job could not be retried (null when it was).
     *
     * @security AdminSession
     *
     * @response 200 {SuccessResponse}
     */
    public function retry(Request $request): JsonResponse
    {
        $data = $request->validate(['uuid' => ['required', 'string']]);
        $result = $this->ops->retry([$data['uuid']]);

        return $this->success(['success' => $result->count() === 1, 'reason' => $result->failed[$data['uuid']] ?? null]);
    }

    /**
     * @input string $uuid
     *
     * @output object $payload
     * @output bool $payload.deleted
     *
     * @security AdminSession
     *
     * @response 200 {SuccessResponse}
     */
    public function forget(Request $request): JsonResponse
    {
        $data = $request->validate(['uuid' => ['required', 'string']]);
        $ok = $this->ops->forgetFailedJob($data['uuid']);

        return $this->success(['deleted' => $ok]);
    }

    /**
     * @input array $uuids
     *
     * @output object $payload
     * @output int $payload.count The jobs pushed back onto their queues.
     * @output object $payload.failed The ones that could not be: uuid => reason.
     *
     * @security AdminSession
     *
     * @response 200 {SuccessResponse}
     */
    public function retryBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uuids' => ['required', 'array', 'min:1'],
            'uuids.*' => ['string'],
        ]);
        $result = $this->ops->retry(array_values($data['uuids']));

        return $this->success(['count' => $result->count(), 'failed' => (object) $result->failed]);
    }

    /**
     * @input array $uuids
     *
     * @output object $payload
     * @output int $payload.count
     *
     * @security AdminSession
     *
     * @response 200 {SuccessResponse}
     */
    public function forgetBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'uuids' => ['required', 'array', 'min:1'],
            'uuids.*' => ['string'],
        ]);
        $count = $this->ops->forgetFailedJobs($data['uuids']);

        return $this->success(['count' => $count]);
    }
}

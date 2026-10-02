<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminJobs\Http\Controllers;

use Dskripchenko\LaravelAdminJobs\Services\JobOperations;
use Dskripchenko\LaravelApi\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The custom routes for cancelling and retrying the failed jobs of
 * Bus::batch() batches.
 */
final class JobBatchController extends ApiController
{
    public function __construct(private readonly JobOperations $ops) {}

    /**
     * @input string $id Batch UUID.
     *
     * @output object $payload
     * @output bool $payload.cancelled
     *
     * @security AdminSession
     *
     * @response 200 {SuccessResponse}
     */
    public function cancel(Request $request): JsonResponse
    {
        $data = $request->validate(['id' => ['required', 'string']]);
        $ok = $this->ops->cancelBatch($data['id']);

        return $this->success(['cancelled' => $ok]);
    }

    /**
     * @input string $id
     *
     * @output object $payload
     * @output bool $payload.dispatched At least one failed job went back onto its queue.
     * @output int $payload.count The jobs pushed back onto their queues.
     * @output object $payload.failed The ones that could not be: uuid => reason.
     *
     * @security AdminSession
     *
     * @response 200 {SuccessResponse}
     */
    public function retryFailed(Request $request): JsonResponse
    {
        $data = $request->validate(['id' => ['required', 'string']]);
        $result = $this->ops->retryBatch($data['id']);

        return $this->success(['dispatched' => $result->count() > 0, 'count' => $result->count(), 'failed' => (object) $result->failed]);
    }
}

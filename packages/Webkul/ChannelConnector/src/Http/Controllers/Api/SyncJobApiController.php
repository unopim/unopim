<?php

namespace Webkul\ChannelConnector\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\ChannelConnector\Models\ChannelSyncJob;
use Webkul\ChannelConnector\Repositories\ChannelSyncJobRepository;
use Webkul\ChannelConnector\Services\SyncJobManager;

class SyncJobApiController extends Controller
{
    public function __construct(
        protected ChannelSyncJobRepository $syncJobRepository,
        protected SyncJobManager $syncJobManager,
    ) {}

    /**
     * List all sync jobs across all connectors with pagination and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ChannelSyncJob::with('connector:id,code,name,channel_type')
            ->orderBy('started_at', 'desc');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($connectorId = $request->get('channel_connector_id')) {
            $query->where('channel_connector_id', $connectorId);
        }

        if ($syncType = $request->get('sync_type')) {
            $query->where('sync_type', $syncType);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->where('started_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->where('started_at', '<=', $dateTo);
        }

        $limit = min((int) $request->get('limit', 15), 100);

        $jobs = $query->paginate($limit);

        return response()->json($jobs);
    }

    /**
     * Show a single sync job with full detail.
     */
    public function show(int $id): JsonResponse
    {
        $job = $this->syncJobRepository->find($id);

        if (! $job) {
            return response()->json(['message' => 'Sync job not found.'], 404);
        }

        $job->load('connector:id,code,name,channel_type');

        $duration = null;
        if ($job->started_at && $job->completed_at) {
            $duration = $job->started_at->diffInSeconds($job->completed_at);
        }

        return response()->json([
            'id'                   => $job->id,
            'job_id'               => $job->job_id,
            'channel_connector_id' => $job->channel_connector_id,
            'connector'            => $job->connector ? [
                'code'         => $job->connector->code,
                'name'         => $job->connector->name,
                'channel_type' => $job->connector->channel_type,
            ] : null,
            'status'          => $job->status,
            'sync_type'       => $job->sync_type,
            'total_products'  => $job->total_products,
            'synced_products' => $job->synced_products,
            'failed_products' => $job->failed_products,
            'progress'        => $job->total_products > 0
                ? round(($job->synced_products + $job->failed_products) / $job->total_products * 100, 1)
                : 0,
            'error_summary' => $job->error_summary,
            'retry_of_id'   => $job->retry_of_id,
            'started_at'    => $job->started_at?->toIso8601String(),
            'completed_at'  => $job->completed_at?->toIso8601String(),
            'duration'      => $duration,
        ]);
    }

    /**
     * Retry a failed sync job.
     */
    public function retry(int $id): JsonResponse
    {
        $job = $this->syncJobRepository->find($id);

        if (! $job) {
            return response()->json(['message' => 'Sync job not found.'], 404);
        }

        if ($job->status !== 'failed') {
            return response()->json(['message' => 'Only failed jobs can be retried.'], 422);
        }

        $retryJob = $this->syncJobManager->retryFailedProducts($job);

        return response()->json([
            'job_id'            => $retryJob->job_id,
            'status'            => $retryJob->status,
            'retry_of'          => $job->job_id,
            'products_to_retry' => count($job->error_summary ?? []),
        ], 202);
    }
}

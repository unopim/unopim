<?php

namespace Webkul\ChannelConnector\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;
use Webkul\ChannelConnector\Repositories\ChannelSyncJobRepository;
use Webkul\ChannelConnector\Services\SyncJobManager;

class SyncApiController extends Controller
{
    public function __construct(
        protected ChannelConnectorRepository $connectorRepository,
        protected ChannelSyncJobRepository $syncJobRepository,
        protected SyncJobManager $syncJobManager,
    ) {}

    public function index(Request $request, string $code): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);
        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        $query = $this->syncJobRepository->scopeQuery(
            fn ($q) => $q->where('channel_connector_id', $connector->id)->orderBy('created_at', 'desc')
        );

        if ($status = $request->get('status')) {
            $query->scopeQuery(fn ($q) => $q->where('status', $status));
        }

        return response()->json($query->paginate(min((int) $request->get('limit', 10), 100)));
    }

    public function trigger(Request $request, string $code): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);
        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        $request->validate([
            'sync_type'     => ['required', Rule::in(['full', 'incremental', 'single'])],
            'product_codes' => ['nullable', 'array'],
            'locales'       => ['nullable', 'array'],
        ]);

        try {
            $job = $this->syncJobManager->triggerSync(
                $connector, $request->input('sync_type'),
                $request->input('product_codes', []), $request->input('locales', []),
            );

            return response()->json([
                'job_id'    => $job->job_id, 'status' => $job->status,
                'sync_type' => $job->sync_type,
            ], 202);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function show(string $code, string $jobId): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);
        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        $job = $this->syncJobRepository->findOneByField('job_id', $jobId);
        if (! $job || $job->channel_connector_id !== $connector->id) {
            return response()->json(['message' => 'Job not found.'], 404);
        }

        return response()->json([
            'job_id'          => $job->job_id, 'status' => $job->status, 'sync_type' => $job->sync_type,
            'total_products'  => $job->total_products, 'synced_products' => $job->synced_products,
            'failed_products' => $job->failed_products, 'started_at' => $job->started_at?->toIso8601String(),
            'completed_at'    => $job->completed_at?->toIso8601String(), 'error_summary' => $job->error_summary,
        ]);
    }

    public function retry(string $code, string $jobId): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);
        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        $job = $this->syncJobRepository->findOneByField('job_id', $jobId);
        if (! $job || $job->channel_connector_id !== $connector->id) {
            return response()->json(['message' => 'Job not found.'], 404);
        }

        if ($job->status !== 'failed') {
            return response()->json(['message' => 'Only failed jobs can be retried.'], 422);
        }

        $retryJob = $this->syncJobManager->retryFailedProducts($job);

        return response()->json([
            'job_id'   => $retryJob->job_id, 'status' => $retryJob->status,
            'retry_of' => $job->job_id, 'products_to_retry' => count($job->error_summary ?? []),
        ], 202);
    }
}

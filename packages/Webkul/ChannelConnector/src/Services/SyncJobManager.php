<?php

namespace Webkul\ChannelConnector\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webkul\ChannelConnector\Events\SyncStarting;
use Webkul\ChannelConnector\Jobs\ProcessSyncJob;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncJob;
use Webkul\ChannelConnector\Repositories\ChannelSyncJobRepository;
use Webkul\Tenant\Cache\TenantCache;

class SyncJobManager
{
    public function __construct(
        protected ChannelSyncJobRepository $syncJobRepository,
    ) {}

    public function triggerSync(
        ChannelConnector $connector,
        string $syncType,
        array $productCodes = [],
        array $locales = [],
    ): ChannelSyncJob {
        $runningJob = ChannelSyncJob::where('channel_connector_id', $connector->id)
            ->whereIn('status', ['pending', 'running'])
            ->where('created_at', '>', now()->subHours(4))
            ->first();

        if ($runningJob) {
            Log::warning('[ChannelConnector] Duplicate sync job prevented', [
                'connector_id'       => $connector->id,
                'sync_type'          => $syncType,
                'running_job_id'     => $runningJob->id,
                'running_job_status' => $runningJob->status,
            ]);

            throw new \RuntimeException(
                trans('channel_connector::app.connectors.duplicate-running')
            );
        }

        Log::info('[ChannelConnector] Triggering sync', [
            'connector_id'  => $connector->id,
            'sync_type'     => $syncType,
            'product_count' => count($productCodes),
            'locales'       => $locales,
        ]);

        $syncJob = $this->syncJobRepository->create([
            'channel_connector_id' => $connector->id,
            'job_id'               => Str::uuid()->toString(),
            'status'               => 'pending',
            'sync_type'            => $syncType,
        ]);

        event(new SyncStarting($syncJob));

        $productIds = [];
        if (! empty($productCodes)) {
            $productIds = \Webkul\Product\Models\Product::whereIn('sku', $productCodes)
                ->pluck('id')
                ->toArray();
        }

        $queueName = 'sync';
        $tenantId = core()->getCurrentTenantId();
        if ($tenantId) {
            $queueName = "tenant-{$tenantId}-sync";
        }

        ProcessSyncJob::dispatch($syncJob->id, $productIds)
            ->onQueue($queueName);

        Log::info('[ChannelConnector] Sync job dispatched to queue', [
            'sync_job_id'  => $syncJob->id,
            'job_id'       => $syncJob->job_id,
            'connector_id' => $connector->id,
            'queue'        => $queueName,
        ]);

        return $syncJob;
    }

    public function getJobStatus(string $jobId): ?ChannelSyncJob
    {
        return ChannelSyncJob::where('job_id', $jobId)->first();
    }

    public function cacheJobProgress(ChannelSyncJob $job): void
    {
        $cacheKey = TenantCache::key("channel_connector.sync_job.{$job->id}.progress");
        Cache::put($cacheKey, [
            'status'          => $job->status,
            'total_products'  => $job->total_products,
            'synced_products' => $job->synced_products,
            'failed_products' => $job->failed_products,
        ], 60);
    }

    public function getCachedJobProgress(int $jobId): ?array
    {
        return Cache::get(TenantCache::key("channel_connector.sync_job.{$jobId}.progress"));
    }

    public function clearJobProgressCache(int $jobId): void
    {
        Cache::forget(TenantCache::key("channel_connector.sync_job.{$jobId}.progress"));
    }

    public function retryFailedProducts(ChannelSyncJob $originalJob): ChannelSyncJob
    {
        $failedProductSkus = collect($originalJob->error_summary ?? [])
            ->pluck('product_sku')
            ->filter()
            ->toArray();

        Log::info('[ChannelConnector] Initiating retry for failed products', [
            'original_job_id' => $originalJob->id,
            'connector_id'    => $originalJob->channel_connector_id,
            'failed_count'    => count($failedProductSkus),
        ]);

        $retryJob = $this->syncJobRepository->create([
            'channel_connector_id' => $originalJob->channel_connector_id,
            'job_id'               => Str::uuid()->toString(),
            'status'               => 'pending',
            'sync_type'            => $originalJob->sync_type,
            'retry_of_id'          => $originalJob->id,
        ]);

        $originalJob->update(['status' => 'retrying']);

        $productIds = \Webkul\Product\Models\Product::whereIn('sku', $failedProductSkus)
            ->pluck('id')
            ->toArray();

        $queueName = 'sync';
        $tenantId = core()->getCurrentTenantId();
        if ($tenantId) {
            $queueName = "tenant-{$tenantId}-sync";
        }

        ProcessSyncJob::dispatch($retryJob->id, $productIds)
            ->onQueue($queueName);

        return $retryJob;
    }
}

<?php

namespace Webkul\ChannelConnector\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Events\SyncCompleted;
use Webkul\ChannelConnector\Events\SyncFailed;
use Webkul\ChannelConnector\Events\SyncProductSynced;
use Webkul\ChannelConnector\Events\SyncProductSyncing;
use Webkul\ChannelConnector\Events\SyncStarted;
use Webkul\ChannelConnector\Models\ChannelSyncJob;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository;
use Webkul\ChannelConnector\Services\AdapterResolver;
use Webkul\ChannelConnector\Services\ConflictResolver;
use Webkul\ChannelConnector\Services\SyncEngine;
use Webkul\ChannelConnector\Services\ValidationEngine;
use Webkul\Product\Models\Product;
use Webkul\Tenant\Cache\TenantCache;
use Webkul\Tenant\Jobs\TenantAwareJob;

class ProcessSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 7200;

    public function __construct(
        protected int $syncJobId,
        protected array $productIds = [],
    ) {
        $this->captureTenantContext();
    }

    public function handle(
        AdapterResolver $adapterResolver,
        SyncEngine $syncEngine,
        ChannelFieldMappingRepository $mappingRepository,
        ConflictResolver $conflictResolver,
    ): void {
        $syncJob = ChannelSyncJob::find($this->syncJobId);

        if (! $syncJob) {
            Log::warning('[ChannelConnector] Sync job not found, aborting', [
                'sync_job_id' => $this->syncJobId,
            ]);

            return;
        }

        Log::info('[ChannelConnector] Sync job started', [
            'sync_job_id'   => $syncJob->id,
            'job_id'        => $syncJob->job_id,
            'connector_id'  => $syncJob->channel_connector_id,
            'sync_type'     => $syncJob->sync_type,
            'product_count' => ! empty($this->productIds) ? count($this->productIds) : 'all',
        ]);

        $connector = $syncJob->connector;

        if (! $connector) {
            Log::error('[ChannelConnector] Connector not found for sync job', [
                'sync_job_id'  => $syncJob->id,
                'connector_id' => $syncJob->channel_connector_id,
            ]);

            $syncJob->update(['status' => 'failed', 'error_summary' => [['error' => 'Connector not found']]]);

            return;
        }

        try {
            $adapter = $adapterResolver->resolve($connector);
        } catch (\Exception $e) {
            Log::error('[ChannelConnector] Adapter resolution failed for sync job', [
                'sync_job_id'  => $syncJob->id,
                'connector_id' => $connector->id,
                'error'        => $e->getMessage(),
            ]);

            $syncJob->update(['status' => 'failed', 'error_summary' => [['error' => $e->getMessage()]]]);
            event(new SyncFailed($syncJob));

            return;
        }

        $syncJob->update(['status' => 'running', 'started_at' => now()]);
        event(new SyncStarted($syncJob));

        $mappings = $mappingRepository->findWhere(['channel_connector_id' => $connector->id]);

        if ($mappings->isEmpty()) {
            Log::warning('[ChannelConnector] No field mappings configured, aborting sync', [
                'sync_job_id'  => $syncJob->id,
                'connector_id' => $connector->id,
            ]);

            $syncJob->update(['status' => 'failed', 'error_summary' => [['error' => 'No field mappings configured']]]);
            event(new SyncFailed($syncJob));

            return;
        }

        $query = Product::query();

        if (! empty($this->productIds)) {
            $query->whereIn('id', $this->productIds);
        }

        if ($syncJob->sync_type === 'incremental' && $connector->last_synced_at) {
            $query->where('updated_at', '>', $connector->last_synced_at);
        }

        $totalProducts = $query->count();
        $syncJob->update(['total_products' => $totalProducts]);

        Log::info('[ChannelConnector] Sync job product query prepared', [
            'sync_job_id'    => $syncJob->id,
            'connector_id'   => $connector->id,
            'total_products' => $totalProducts,
        ]);

        $errors = [];
        $synced = 0;
        $failed = 0;
        $conflicted = 0;
        $processed = 0;

        $conflictStrategy = $connector->settings['conflict_strategy'] ?? 'always_ask';

        $query->chunk(100, function ($products) use (
            $adapter, $syncEngine, $conflictResolver, $mappings, $syncJob, $connector, $conflictStrategy, $totalProducts, &$errors, &$synced, &$failed, &$conflicted, &$processed
        ) {
            foreach ($products as $product) {
                try {
                    event(new SyncProductSyncing($product, $connector));

                    // Check for existing ProductChannelMapping with a stored hash
                    $existingMapping = ProductChannelMapping::where('channel_connector_id', $connector->id)
                        ->where('product_id', $product->id)
                        ->where('entity_type', 'product')
                        ->first();

                    // Conflict detection: only when a mapping with a data_hash already exists
                    if ($existingMapping && $existingMapping->data_hash) {
                        $conflict = $conflictResolver->detectConflict(
                            $product,
                            $existingMapping,
                            $adapter,
                            $mappings,
                            $syncJob->id,
                        );

                        if ($conflict) {
                            // Conflict detected - handle based on connector's conflict strategy
                            if ($conflictStrategy === 'pim_always_wins') {
                                $conflictResolver->resolveConflict($conflict, 'pim_wins');
                                // Continue with normal sync after auto-resolve
                            } elseif ($conflictStrategy === 'channel_always_wins') {
                                $conflictResolver->resolveConflict($conflict, 'channel_wins');
                                $conflicted++;

                                continue;
                            } else {
                                // 'always_ask' (default) - skip product, conflict record created
                                $conflicted++;

                                continue;
                            }
                        }
                    }

                    $payload = $syncEngine->prepareSyncPayload($product, $mappings, $connector);

                    $validationRules = $connector->settings['validation_rules'] ?? [];
                    if (! empty($validationRules)) {
                        $validationResult = ValidationEngine::validate($payload, $validationRules);
                        if (! $validationResult->valid) {
                            $failed++;
                            $errors[] = [
                                'product_sku' => $product->sku ?? $product->id,
                                'errors'      => array_map(fn ($e) => $e['message'], $validationResult->errors),
                            ];

                            continue;
                        }
                    }

                    $result = $adapter->syncProduct($product, $payload);

                    if ($result->success) {
                        ProductChannelMapping::updateOrCreate(
                            [
                                'channel_connector_id' => $connector->id,
                                'product_id'           => $product->id,
                                'entity_type'          => 'product',
                            ],
                            [
                                'external_id'    => $result->externalId,
                                'sync_status'    => 'synced',
                                'last_synced_at' => now(),
                                'data_hash'      => $result->dataHash ?? $syncEngine->computeDataHash($payload),
                            ]
                        );

                        $synced++;

                        Log::debug('[ChannelConnector] Product synced successfully', [
                            'connector_id' => $connector->id,
                            'product_id'   => $product->id,
                            'external_id'  => $result->externalId,
                        ]);
                    } else {
                        $failed++;
                        $errors[] = [
                            'product_sku' => $product->sku ?? $product->id,
                            'errors'      => $result->errors,
                        ];

                        Log::warning('[ChannelConnector] Product sync failed', [
                            'connector_id' => $connector->id,
                            'product_id'   => $product->id,
                            'product_sku'  => $product->sku ?? $product->id,
                            'errors'       => $result->errors,
                        ]);
                    }

                    event(new SyncProductSynced($product, $result));
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'product_sku' => $product->sku ?? $product->id,
                        'errors'      => [$e->getMessage()],
                    ];

                    Log::error('[ChannelConnector] Product sync exception', [
                        'connector_id' => $connector->id,
                        'product_id'   => $product->id,
                        'product_sku'  => $product->sku ?? $product->id,
                        'error'        => $e->getMessage(),
                        'sync_job_id'  => $syncJob->id,
                    ]);
                }

                $processed++;

                Cache::put(TenantCache::key("channel_connector.sync_job.{$syncJob->id}.progress"), [
                    'status'          => 'running',
                    'total_products'  => $syncJob->total_products,
                    'synced_products' => $synced,
                    'failed_products' => $failed,
                ], 60);
            }

            $syncJob->update([
                'synced_products' => $synced,
                'failed_products' => $failed,
            ]);

            Log::info('[ChannelConnector] Sync batch progress', [
                'sync_job_id'  => $syncJob->id,
                'connector_id' => $connector->id,
                'processed'    => $processed,
                'total'        => $totalProducts,
                'synced'       => $synced,
                'failed'       => $failed,
                'conflicted'   => $conflicted,
            ]);
        });

        Log::info('[ChannelConnector] Sync job processing complete', [
            'sync_job_id'    => $syncJob->id,
            'connector_id'   => $connector->id,
            'total_products' => $totalProducts,
            'synced'         => $synced,
            'failed'         => $failed,
            'conflicted'     => $conflicted,
            'error_count'    => count($errors),
        ]);

        $status = $failed > 0 && $synced === 0 && $conflicted === 0 ? 'failed' : 'completed';

        $syncJob->update([
            'status'         => $status,
            'completed_at'   => now(),
            'error_summary'  => ! empty($errors) ? $errors : null,
        ]);

        Cache::forget(TenantCache::key("channel_connector.sync_job.{$syncJob->id}.progress"));

        $connector->update(['last_synced_at' => now()]);

        if ($status === 'completed') {
            event(new SyncCompleted($syncJob));
        } else {
            event(new SyncFailed($syncJob));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[ChannelConnector] Sync job failed permanently', [
            'job_id'    => $this->syncJobId,
            'exception' => $exception->getMessage(),
        ]);

        $syncJob = \Webkul\ChannelConnector\Models\ChannelSyncJob::find($this->syncJobId);
        if ($syncJob) {
            $syncJob->update([
                'status'        => 'failed',
                'completed_at'  => now(),
                'error_summary' => [['error' => $exception->getMessage()]],
            ]);
            event(new \Webkul\ChannelConnector\Events\SyncFailed($syncJob));
        }
    }
}

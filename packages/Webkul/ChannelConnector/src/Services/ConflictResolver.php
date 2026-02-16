<?php

namespace Webkul\ChannelConnector\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Webkul\ChannelConnector\Contracts\ChannelAdapterContract;
use Webkul\ChannelConnector\Events\ConflictDetected;
use Webkul\ChannelConnector\Events\ConflictResolved;
use Webkul\ChannelConnector\Models\ChannelSyncConflict;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\Repositories\ChannelSyncConflictRepository;
use Webkul\Product\Models\Product;

class ConflictResolver
{
    public function __construct(
        protected ChannelSyncConflictRepository $conflictRepository,
        protected SyncEngine $syncEngine,
    ) {}

    /**
     * Detect conflict by comparing PIM hash, stored hash, and channel hash.
     * Conflict exists when BOTH PIM and channel have changed from stored hash.
     *
     * Returns null if no conflict (normal sync should proceed), or the
     * created ChannelSyncConflict model when a conflict is detected.
     */
    public function detectConflict(
        Product $product,
        ProductChannelMapping $pcMapping,
        ChannelAdapterContract $adapter,
        Collection $mappings,
        int $syncJobId,
    ): ?ChannelSyncConflict {
        Log::info('[ChannelConnector] Starting conflict detection', [
            'product_id'   => $product->id,
            'connector_id' => $pcMapping->channel_connector_id,
            'sync_job_id'  => $syncJobId,
        ]);

        // 1. Compute current PIM hash from product values
        $pimPayload = $this->syncEngine->prepareSyncPayload($product, $mappings);
        $pimHash = $this->syncEngine->computeDataHash($pimPayload);

        Log::debug('[ChannelConnector] PIM hash computed for conflict check', [
            'product_id'   => $product->id,
            'connector_id' => $pcMapping->channel_connector_id,
            'pim_hash'     => $pimHash,
            'stored_hash'  => $pcMapping->data_hash,
        ]);

        // 2. If PIM hash matches stored hash, no PIM changes - no conflict possible
        if ($pimHash === $pcMapping->data_hash) {
            Log::debug('[ChannelConnector] No PIM changes detected, skipping conflict check', [
                'product_id'   => $product->id,
                'connector_id' => $pcMapping->channel_connector_id,
            ]);

            return null;
        }

        // 3. Fetch current channel data and compute channel hash
        $channelData = $adapter->fetchProduct($pcMapping->external_id);

        if ($channelData === null) {
            Log::debug('[ChannelConnector] Product deleted from channel, no conflict', [
                'product_id'   => $product->id,
                'connector_id' => $pcMapping->channel_connector_id,
                'external_id'  => $pcMapping->external_id,
            ]);

            // Product deleted from channel — not a conflict, normal sync will re-create
            return null;
        }

        $channelHash = $this->syncEngine->computeDataHash($channelData);

        Log::debug('[ChannelConnector] Channel hash computed for conflict check', [
            'product_id'   => $product->id,
            'connector_id' => $pcMapping->channel_connector_id,
            'channel_hash' => $channelHash,
            'stored_hash'  => $pcMapping->data_hash,
        ]);

        // 4. If channel hash matches stored hash, only PIM changed — normal sync, no conflict
        if ($channelHash === $pcMapping->data_hash) {
            Log::debug('[ChannelConnector] Only PIM changed, no conflict', [
                'product_id'   => $product->id,
                'connector_id' => $pcMapping->channel_connector_id,
            ]);

            return null;
        }

        // 5. Both PIM and channel changed since last sync — CONFLICT
        Log::warning('[ChannelConnector] Conflict detected: both PIM and channel modified', [
            'product_id'   => $product->id,
            'connector_id' => $pcMapping->channel_connector_id,
            'pim_hash'     => $pimHash,
            'channel_hash' => $channelHash,
            'stored_hash'  => $pcMapping->data_hash,
        ]);

        $conflictingFields = $this->buildConflictingFields($pimPayload, $channelData, $mappings);

        $conflict = $this->conflictRepository->create([
            'channel_connector_id' => $pcMapping->channel_connector_id,
            'channel_sync_job_id'  => $syncJobId,
            'product_id'           => $product->id,
            'conflict_type'        => 'both_modified',
            'conflicting_fields'   => $conflictingFields,
            'pim_modified_at'      => $product->updated_at,
            'channel_modified_at'  => now(),
            'resolution_status'    => 'unresolved',
        ]);

        event(new ConflictDetected($conflict));

        Log::info('[ChannelConnector] Sync conflict record created', [
            'conflict_id'  => $conflict->id,
            'product_id'   => $product->id,
            'connector_id' => $pcMapping->channel_connector_id,
            'fields_count' => count($conflictingFields),
        ]);

        return $conflict;
    }

    /**
     * Resolve a conflict with a given strategy.
     *
     * @param  string  $resolution  One of: pim_wins, channel_wins, merged, dismissed
     * @param  array|null  $fieldOverrides  Per-field overrides when using 'merged' strategy
     */
    public function resolveConflict(
        ChannelSyncConflict $conflict,
        string $resolution,
        ?array $fieldOverrides = null,
    ): void {
        $resolutionDetails = [
            'strategy'   => $resolution,
            'applied_at' => now()->toIso8601String(),
        ];

        switch ($resolution) {
            case 'pim_wins':
                $resolutionDetails['action'] = 'pim_data_pushed_to_channel';
                $this->applyPimWins($conflict);
                break;

            case 'channel_wins':
                $resolutionDetails['action'] = 'channel_data_pulled_to_pim';
                $this->applyChannelWins($conflict);
                break;

            case 'merged':
                $resolutionDetails['action'] = 'per_field_merge_applied';
                $resolutionDetails['field_overrides'] = $fieldOverrides;
                $this->applyMerged($conflict, $fieldOverrides ?? []);
                break;

            case 'dismissed':
                $resolutionDetails['action'] = 'conflict_dismissed_no_changes';
                break;
        }

        $conflict->update([
            'resolution_status'  => $resolution,
            'resolution_details' => $resolutionDetails,
            'resolved_by'        => auth()->guard('admin')->id(),
            'resolved_at'        => now(),
        ]);

        event(new ConflictResolved($conflict->fresh()));

        Log::info('[ChannelConnector] Sync conflict resolved', [
            'conflict_id'   => $conflict->id,
            'resolution'    => $resolution,
            'resolved_by'   => auth()->guard('admin')->id(),
            'connector_id'  => $conflict->channel_connector_id,
            'product_id'    => $conflict->product_id,
        ]);
    }

    /**
     * Build per-field diff structure with locale awareness.
     *
     * Returns an array keyed by field code, each containing pim_value, channel_value,
     * is_locale_specific flag, and per-locale values when applicable.
     */
    public function buildConflictingFields(array $pimValues, array $channelValues, Collection $mappings): array
    {
        $conflicting = [];

        $pimCommon = $pimValues['common'] ?? [];
        $channelCommon = $channelValues['common'] ?? [];
        $pimLocales = $pimValues['locales'] ?? [];
        $channelLocales = $channelValues['locales'] ?? [];

        // Compare common (non-locale-specific) fields
        $allCommonFields = array_unique(array_merge(array_keys($pimCommon), array_keys($channelCommon)));

        foreach ($allCommonFields as $field) {
            $pimVal = $pimCommon[$field] ?? null;
            $channelVal = $channelCommon[$field] ?? null;

            if ($pimVal !== $channelVal) {
                $conflicting[$field] = [
                    'pim_value'          => $pimVal,
                    'channel_value'      => $channelVal,
                    'is_locale_specific' => false,
                    'locales'            => [],
                ];
            }
        }

        // Compare locale-specific fields
        $allLocales = array_unique(array_merge(array_keys($pimLocales), array_keys($channelLocales)));

        foreach ($allLocales as $locale) {
            $pimLocaleFields = $pimLocales[$locale] ?? [];
            $channelLocaleFields = $channelLocales[$locale] ?? [];
            $allFields = array_unique(array_merge(array_keys($pimLocaleFields), array_keys($channelLocaleFields)));

            foreach ($allFields as $field) {
                $pimVal = $pimLocaleFields[$field] ?? null;
                $channelVal = $channelLocaleFields[$field] ?? null;

                if ($pimVal !== $channelVal) {
                    if (! isset($conflicting[$field])) {
                        $conflicting[$field] = [
                            'pim_value'          => null,
                            'channel_value'      => null,
                            'is_locale_specific' => true,
                            'locales'            => [],
                        ];
                    }

                    $conflicting[$field]['is_locale_specific'] = true;
                    $conflicting[$field]['locales'][$locale] = [
                        'pim_value'     => $pimVal,
                        'channel_value' => $channelVal,
                    ];
                }
            }
        }

        return $conflicting;
    }

    /**
     * Apply PIM wins: re-sync current PIM data to the channel.
     */
    protected function applyPimWins(ChannelSyncConflict $conflict): void
    {
        $product = $conflict->product;
        $connector = $conflict->connector;

        if (! $product || ! $connector) {
            Log::error('[ChannelConnector] Cannot apply pim_wins resolution: missing product or connector', [
                'conflict_id'  => $conflict->id,
                'product_id'   => $conflict->product_id,
                'connector_id' => $conflict->channel_connector_id,
            ]);

            return;
        }

        Log::info('[ChannelConnector] Applying pim_wins resolution', [
            'conflict_id'  => $conflict->id,
            'product_id'   => $product->id,
            'connector_id' => $connector->id,
        ]);

        $mappings = $connector->fieldMappings;
        $payload = $this->syncEngine->prepareSyncPayload($product, $mappings);

        $adapter = app(AdapterResolver::class)->resolve($connector);
        $result = $adapter->syncProduct($product, $payload);

        if ($result->success) {
            $pcMapping = ProductChannelMapping::where('channel_connector_id', $connector->id)
                ->where('product_id', $product->id)
                ->first();

            if ($pcMapping) {
                $pcMapping->update([
                    'data_hash'      => $result->dataHash ?? $this->syncEngine->computeDataHash($payload),
                    'sync_status'    => 'synced',
                    'last_synced_at' => now(),
                ]);
            }
        }
    }

    /**
     * Apply channel wins: pull channel data into PIM product values.
     */
    protected function applyChannelWins(ChannelSyncConflict $conflict): void
    {
        $product = $conflict->product;
        $connector = $conflict->connector;

        if (! $product || ! $connector) {
            Log::error('[ChannelConnector] Cannot apply channel_wins resolution: missing product or connector', [
                'conflict_id'  => $conflict->id,
                'product_id'   => $conflict->product_id,
                'connector_id' => $conflict->channel_connector_id,
            ]);

            return;
        }

        Log::info('[ChannelConnector] Applying channel_wins resolution', [
            'conflict_id'  => $conflict->id,
            'product_id'   => $product->id,
            'connector_id' => $connector->id,
        ]);

        $pcMapping = ProductChannelMapping::where('channel_connector_id', $connector->id)
            ->where('product_id', $product->id)
            ->first();

        if (! $pcMapping) {
            return;
        }

        $adapter = app(AdapterResolver::class)->resolve($connector);
        $channelData = $adapter->fetchProduct($pcMapping->external_id);

        if ($channelData === null) {
            return;
        }

        // Update PIM product values from channel data using field mappings
        $this->applyChannelDataToProduct($product, $channelData, $connector->fieldMappings);

        // Update the stored hash to reflect channel state
        $pcMapping->update([
            'data_hash'      => $this->syncEngine->computeDataHash($channelData),
            'sync_status'    => 'synced',
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Apply merged resolution: per-field overrides determine which side wins for each field.
     */
    protected function applyMerged(ChannelSyncConflict $conflict, array $fieldOverrides): void
    {
        $product = $conflict->product;
        $connector = $conflict->connector;

        if (! $product || ! $connector) {
            Log::error('[ChannelConnector] Cannot apply merged resolution: missing product or connector', [
                'conflict_id'  => $conflict->id,
                'product_id'   => $conflict->product_id,
                'connector_id' => $conflict->channel_connector_id,
            ]);

            return;
        }

        Log::info('[ChannelConnector] Applying merged resolution', [
            'conflict_id'     => $conflict->id,
            'product_id'      => $product->id,
            'connector_id'    => $connector->id,
            'overrides_count' => count($fieldOverrides),
        ]);

        $pcMapping = ProductChannelMapping::where('channel_connector_id', $connector->id)
            ->where('product_id', $product->id)
            ->first();

        if (! $pcMapping) {
            return;
        }

        $adapter = app(AdapterResolver::class)->resolve($connector);
        $channelData = $adapter->fetchProduct($pcMapping->external_id);

        if ($channelData === null) {
            return;
        }

        $mappings = $connector->fieldMappings;
        $pimPayload = $this->syncEngine->prepareSyncPayload($product, $mappings);

        // Build merged payload based on field overrides
        $mergedPayload = $this->buildMergedPayload($pimPayload, $channelData, $fieldOverrides);

        // Fields where channel_wins need to be pulled into PIM
        $channelWinsFields = collect($fieldOverrides)
            ->filter(fn ($winner) => $winner === 'channel')
            ->keys()
            ->all();

        if (! empty($channelWinsFields)) {
            $this->applyChannelDataToProduct($product, $channelData, $mappings, $channelWinsFields);
        }

        // Push the merged result to channel
        $result = $adapter->syncProduct($product->fresh(), $mergedPayload);

        if ($result->success) {
            $pcMapping->update([
                'data_hash'      => $result->dataHash ?? $this->syncEngine->computeDataHash($mergedPayload),
                'sync_status'    => 'synced',
                'last_synced_at' => now(),
            ]);
        }
    }

    /**
     * Build a merged payload selecting PIM or channel values per field.
     */
    protected function buildMergedPayload(array $pimPayload, array $channelData, array $fieldOverrides): array
    {
        $merged = [
            'common'  => [],
            'locales' => [],
        ];

        $pimCommon = $pimPayload['common'] ?? [];
        $channelCommon = $channelData['common'] ?? [];
        $allCommon = array_unique(array_merge(array_keys($pimCommon), array_keys($channelCommon)));

        foreach ($allCommon as $field) {
            $winner = $fieldOverrides[$field] ?? 'pim';
            $merged['common'][$field] = $winner === 'channel'
                ? ($channelCommon[$field] ?? $pimCommon[$field] ?? null)
                : ($pimCommon[$field] ?? $channelCommon[$field] ?? null);
        }

        $pimLocales = $pimPayload['locales'] ?? [];
        $channelLocales = $channelData['locales'] ?? [];
        $allLocales = array_unique(array_merge(array_keys($pimLocales), array_keys($channelLocales)));

        foreach ($allLocales as $locale) {
            $pimFields = $pimLocales[$locale] ?? [];
            $channelFields = $channelLocales[$locale] ?? [];
            $allFields = array_unique(array_merge(array_keys($pimFields), array_keys($channelFields)));

            foreach ($allFields as $field) {
                $winner = $fieldOverrides[$field] ?? 'pim';
                $merged['locales'][$locale][$field] = $winner === 'channel'
                    ? ($channelFields[$field] ?? $pimFields[$field] ?? null)
                    : ($pimFields[$field] ?? $channelFields[$field] ?? null);
            }
        }

        return $merged;
    }

    /**
     * Apply channel data back to PIM product values through field mappings.
     */
    protected function applyChannelDataToProduct(
        Product $product,
        array $channelData,
        Collection $mappings,
        ?array $onlyFields = null,
    ): void {
        $values = $product->values ?? [];
        $channelCommon = $channelData['common'] ?? [];
        $channelLocales = $channelData['locales'] ?? [];

        foreach ($mappings as $mapping) {
            if ($mapping->direction === 'export') {
                continue;
            }

            $channelField = $mapping->channel_field;

            if ($onlyFields !== null && ! in_array($channelField, $onlyFields)) {
                continue;
            }

            $attributeCode = $mapping->unopim_attribute_code;
            $localeMappingConfig = $mapping->locale_mapping ?? [];

            // Apply common values
            if (isset($channelCommon[$channelField])) {
                $values['common'][$attributeCode] = $channelCommon[$channelField];
            }

            // Apply locale-specific values
            if (! empty($localeMappingConfig)) {
                foreach ($localeMappingConfig as $unopimLocale => $channelLocale) {
                    if (isset($channelLocales[$channelLocale][$channelField])) {
                        $values['locale_specific'][$unopimLocale][$attributeCode] = $channelLocales[$channelLocale][$channelField];
                    }
                }
            }
        }

        $product->update(['values' => $values]);
    }
}

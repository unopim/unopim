<?php

namespace Webkul\ChannelConnector\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Attribute\Models\Attribute;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ChannelSyncConflict;
use Webkul\ChannelConnector\Models\ChannelWebhookEvent;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository;
use Webkul\Product\Models\Product;
use Webkul\Tenant\Jobs\TenantAwareJob;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TenantAwareJob;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        protected int $connectorId,
        protected array $payload,
        protected ?string $webhookEventId = null,
    ) {
        $this->captureTenantContext();
    }

    public function handle(ChannelFieldMappingRepository $mappingRepository): void
    {
        $connector = ChannelConnector::find($this->connectorId);

        if (! $connector) {
            Log::warning('[ChannelConnector] Webhook connector not found', [
                'connector_id' => $this->connectorId,
            ]);

            return;
        }

        $settings = $connector->settings ?? [];
        $inboundStrategy = $settings['inbound_strategy'] ?? 'flag_for_review';
        $eventType = $this->payload['event'] ?? $this->payload['type'] ?? null;

        Log::info('[ChannelConnector] Processing webhook', [
            'connector_id'     => $connector->id,
            'event_type'       => $eventType,
            'strategy'         => $inboundStrategy,
            'payload_keys'     => array_keys($this->payload),
            'webhook_event_id' => $this->webhookEventId,
        ]);

        // Idempotency check: skip if this webhook event was already processed
        if ($this->webhookEventId) {
            if (ChannelWebhookEvent::isProcessed($connector->id, $this->webhookEventId)) {
                Log::info('[ChannelConnector] Webhook already processed, skipping', [
                    'connector_id'     => $connector->id,
                    'webhook_event_id' => $this->webhookEventId,
                ]);

                return;
            }

            // Mark webhook event as processed
            ChannelWebhookEvent::markAsProcessed($connector->id, $this->webhookEventId, $eventType);
        }

        if ($inboundStrategy === 'ignore') {
            Log::info('[ChannelConnector] Webhook ignored per inbound strategy', [
                'connector_id' => $connector->id,
            ]);

            return;
        }

        switch ($eventType) {
            case 'product.updated':
            case 'products/update':
                $this->handleProductUpdated($connector, $inboundStrategy, $mappingRepository);
                break;

            case 'product.created':
            case 'products/create':
                $this->handleProductCreated($connector, $inboundStrategy, $mappingRepository);
                break;

            case 'product.deleted':
            case 'products/delete':
                $this->handleProductDeleted($connector);
                break;

            default:
                Log::warning('[ChannelConnector] Unsupported webhook event', [
                    'event'        => $eventType,
                    'connector_id' => $connector->id,
                ]);
                break;
        }
    }

    protected function handleProductUpdated(
        ChannelConnector $connector,
        string $inboundStrategy,
        ChannelFieldMappingRepository $mappingRepository,
    ): void {
        $externalId = $this->extractExternalId();

        if (! $externalId) {
            Log::warning('[ChannelConnector] Webhook missing external product ID', [
                'connector_id' => $connector->id,
            ]);

            return;
        }

        $pcMapping = ProductChannelMapping::where('channel_connector_id', $connector->id)
            ->where('external_id', $externalId)
            ->where('entity_type', 'product')
            ->first();

        if (! $pcMapping || ! $pcMapping->product) {
            Log::warning('[ChannelConnector] No PIM product mapping found for external ID', [
                'connector_id' => $connector->id,
                'external_id'  => $externalId,
            ]);

            return;
        }

        $product = $pcMapping->product;

        if ($inboundStrategy === 'auto_update') {
            $this->applyInboundUpdate($product, $connector, $mappingRepository);

            $pcMapping->update([
                'sync_status'    => 'synced',
                'last_synced_at' => now(),
            ]);

            Log::info('[ChannelConnector] Product auto-updated from webhook', [
                'product_id'   => $product->id,
                'connector_id' => $connector->id,
            ]);
        } elseif ($inboundStrategy === 'flag_for_review') {
            ChannelSyncConflict::create([
                'channel_connector_id' => $connector->id,
                'product_id'           => $product->id,
                'conflict_type'        => 'field_mismatch',
                'conflicting_fields'   => $this->extractChangedFields(),
                'channel_modified_at'  => now(),
                'resolution_status'    => 'pending',
            ]);

            Log::info('[ChannelConnector] Product flagged for review from webhook', [
                'product_id'   => $product->id,
                'connector_id' => $connector->id,
            ]);
        }
    }

    protected function handleProductCreated(
        ChannelConnector $connector,
        string $inboundStrategy,
        ChannelFieldMappingRepository $mappingRepository,
    ): void {
        $externalId = $this->extractExternalId();

        if (! $externalId) {
            Log::warning('[ChannelConnector] Webhook missing external product ID for create event', [
                'connector_id' => $connector->id,
            ]);

            return;
        }

        if ($inboundStrategy === 'auto_update') {
            Log::info('[ChannelConnector] Product created event received, auto-create not supported via webhook', [
                'connector_id' => $connector->id,
                'external_id'  => $externalId,
            ]);
        } elseif ($inboundStrategy === 'flag_for_review') {
            ChannelSyncConflict::create([
                'channel_connector_id' => $connector->id,
                'conflict_type'        => 'new_in_channel',
                'conflicting_fields'   => $this->extractChangedFields(),
                'channel_modified_at'  => now(),
                'resolution_status'    => 'pending',
            ]);

            Log::info('[ChannelConnector] New product in channel flagged for review', [
                'connector_id' => $connector->id,
                'external_id'  => $externalId,
            ]);
        }
    }

    protected function handleProductDeleted(ChannelConnector $connector): void
    {
        $externalId = $this->extractExternalId();

        if (! $externalId) {
            Log::warning('[ChannelConnector] Webhook missing external product ID for delete event', [
                'connector_id' => $connector->id,
            ]);

            return;
        }

        $updated = ProductChannelMapping::where('channel_connector_id', $connector->id)
            ->where('external_id', $externalId)
            ->where('entity_type', 'product')
            ->update(['sync_status' => 'deleted']);

        Log::info('[ChannelConnector] Product marked as deleted from webhook', [
            'connector_id' => $connector->id,
            'external_id'  => $externalId,
            'updated'      => $updated,
        ]);
    }

    protected function applyInboundUpdate(
        Product $product,
        ChannelConnector $connector,
        ChannelFieldMappingRepository $mappingRepository,
    ): void {
        $mappings = $mappingRepository->findWhere([
            'channel_connector_id' => $connector->id,
        ])->whereIn('direction', ['import', 'both']);

        if ($mappings->isEmpty()) {
            return;
        }

        $inboundData = $this->payload['data'] ?? $this->payload['product'] ?? $this->payload;
        $values = $product->values ?? [];

        $settings = $connector->settings ?? [];
        $currentChannelCode = $settings['default_channel'] ?? core()->getDefaultChannelCode();
        $currentLocaleCode = $this->payload['locale'] ?? $settings['default_locale'] ?? core()->getDefaultLocaleCodeFromDefaultChannel();

        foreach ($mappings as $mapping) {
            $channelField = $mapping->channel_field;
            $attributeCode = $mapping->unopim_attribute_code;

            if (! isset($inboundData[$channelField])) {
                continue;
            }

            $attribute = Attribute::where('code', $attributeCode)->first();

            if (! $attribute) {
                Log::warning('[ChannelConnector] Attribute not found for inbound mapping, skipping', [
                    'attribute_code' => $attributeCode,
                    'channel_field'  => $channelField,
                    'connector_id'   => $connector->id,
                ]);

                continue;
            }

            $newValue = $inboundData[$channelField];

            $attribute->setProductValue($newValue, $values, $currentChannelCode, $currentLocaleCode);
        }

        $product->values = $values;
        $product->save();
    }

    protected function extractExternalId(): ?string
    {
        return $this->payload['id']
            ?? $this->payload['data']['id']
            ?? $this->payload['product']['id']
            ?? $this->payload['resource_id']
            ?? null;
    }

    protected function extractChangedFields(): array
    {
        $data = $this->payload['data'] ?? $this->payload['product'] ?? $this->payload;
        $fields = [];

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (! in_array($key, ['id', 'created_at', 'updated_at', 'admin_graphql_api_id'])) {
                    $fields[$key] = [
                        'channel_value' => $value,
                    ];
                }
            }
        }

        return $fields;
    }
}

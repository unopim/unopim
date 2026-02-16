<?php

namespace Webkul\ChannelConnector\Services;

use Illuminate\Support\Facades\Cache;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository;
use Webkul\Tenant\Cache\TenantCache;

class MappingService
{
    protected array $commonMappings = [
        'sku'         => 'sku',
        'name'        => 'title',
        'description' => 'descriptionHtml',
        'price'       => 'price',
        'weight'      => 'weight',
        'status'      => 'status',
    ];

    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected ChannelFieldMappingRepository $mappingRepository,
        protected AdapterResolver $adapterResolver,
    ) {}

    public function getAutoSuggestedMappings(ChannelConnector $connector): array
    {
        try {
            $cacheKey = TenantCache::key("channel_connector.{$connector->id}.channel_fields");
            $channelFields = Cache::remember($cacheKey, 300, function () use ($connector) {
                $adapter = $this->adapterResolver->resolve($connector);

                return collect($adapter->getChannelFields());
            });
        } catch (\Exception) {
            $channelFields = collect();
        }

        $suggestions = [];

        foreach ($this->commonMappings as $unopimCode => $channelCode) {
            $channelField = $channelFields->firstWhere('code', $channelCode);

            if ($channelField) {
                $suggestions[] = [
                    'unopim_attribute_code' => $unopimCode,
                    'channel_field'         => $channelCode,
                    'direction'             => 'export',
                    'is_translatable'       => $channelField['is_translatable'] ?? false,
                ];
            }
        }

        return $suggestions;
    }

    public function validateMappings(array $mappings): array
    {
        $errors = [];

        $seen = [];

        foreach ($mappings as $index => $mapping) {
            $key = ($mapping['unopim_attribute_code'] ?? '').'|'.($mapping['channel_field'] ?? '');

            if (isset($seen[$key])) {
                $errors["mappings.{$index}"] = 'Duplicate mapping for attribute and field combination.';
            }

            $seen[$key] = true;

            if (empty($mapping['unopim_attribute_code']) || empty($mapping['channel_field'])) {
                $errors["mappings.{$index}"] = 'Both attribute code and channel field are required.';
            }

            if (! in_array($mapping['direction'] ?? '', ['export', 'import', 'both'])) {
                $errors["mappings.{$index}.direction"] = 'Invalid direction.';
            }
        }

        return $errors;
    }

    public function getLocaleMapping(ChannelConnector $connector): array
    {
        $settings = $connector->settings ?? [];

        return $settings['locale_mapping'] ?? [];
    }

    public function saveMappings(ChannelConnector $connector, array $mappings): void
    {
        // Delete existing mappings
        $this->mappingRepository->deleteWhere(['channel_connector_id' => $connector->id]);

        // Create new mappings
        foreach ($mappings as $index => $mapping) {
            $this->mappingRepository->create([
                'channel_connector_id'  => $connector->id,
                'unopim_attribute_code' => $mapping['unopim_attribute_code'],
                'channel_field'         => $mapping['channel_field'],
                'direction'             => $mapping['direction'] ?? 'export',
                'transformation'        => $mapping['transformation'] ?? null,
                'locale_mapping'        => $mapping['locale_mapping'] ?? null,
                'sort_order'            => $index,
            ]);
        }

        // Invalidate mapping caches for this connector
        Cache::forget(TenantCache::key("channel_connector.{$connector->id}.mappings"));
        Cache::forget(TenantCache::key("channel_connector.{$connector->id}.channel_fields"));
    }

    public function getMappingsForConnector(ChannelConnector $connector): \Illuminate\Support\Collection
    {
        $cacheKey = TenantCache::key("channel_connector.{$connector->id}.mappings");

        return Cache::remember($cacheKey, 300, function () use ($connector) {
            return $this->mappingRepository->findWhere(['channel_connector_id' => $connector->id]);
        });
    }
}

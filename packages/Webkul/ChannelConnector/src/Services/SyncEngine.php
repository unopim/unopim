<?php

namespace Webkul\ChannelConnector\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Webkul\Attribute\Models\Attribute;
use Webkul\ChannelConnector\Models\ChannelConnector;
use Webkul\ChannelConnector\Models\ProductChannelMapping;
use Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository;
use Webkul\Product\Models\Product;

class SyncEngine
{
    public function __construct(
        protected ChannelFieldMappingRepository $mappingRepository,
    ) {}

    public function prepareSyncPayload(Product $product, Collection $mappings, ?ChannelConnector $connector = null): array
    {
        Log::debug('[ChannelConnector] Preparing sync payload', [
            'product_id'    => $product->id,
            'mapping_count' => $mappings->count(),
        ]);

        $localeValues = [];
        $commonValues = [];

        $attributeCodes = $mappings->pluck('unopim_attribute_code')->unique();
        $attributesByCode = Attribute::whereIn('code', $attributeCodes)->get()->keyBy('code');

        foreach ($mappings as $mapping) {
            if ($mapping->direction === 'import') {
                continue;
            }

            $attributeCode = $mapping->unopim_attribute_code;
            $channelField = $mapping->channel_field;
            $localeMappingConfig = $mapping->locale_mapping ?? [];

            $attribute = $attributesByCode->get($attributeCode);

            if (! $attribute) {
                Log::warning('[ChannelConnector] Attribute not found during payload preparation', [
                    'product_id'     => $product->id,
                    'attribute_code' => $attributeCode,
                    'channel_field'  => $channelField,
                ]);

                continue;
            }

            $transformationRules = $mapping->transformation ?? [];

            if ($attribute->value_per_locale && ! empty($localeMappingConfig)) {
                foreach ($localeMappingConfig as $unopimLocale => $channelLocale) {
                    $value = $attribute->getValueFromProductValues($product->values, $unopimLocale);

                    if ($value !== null) {
                        if (! empty($transformationRules)) {
                            $value = TransformationEngine::apply($value, $transformationRules);
                        }

                        if ($connector && $attribute->type === 'price' && ! empty($connector->settings['pricing_rules'] ?? [])) {
                            $value = TransformationEngine::apply($value, $connector->settings['pricing_rules']);
                        }

                        $localeValues[$channelLocale][$channelField] = $value;
                    }
                }
            } else {
                $value = $attribute->getValueFromProductValues($product->values);

                if ($value !== null) {
                    if (! empty($transformationRules)) {
                        $value = TransformationEngine::apply($value, $transformationRules);
                    }

                    if ($connector && $attribute->type === 'price' && ! empty($connector->settings['pricing_rules'] ?? [])) {
                        $value = TransformationEngine::apply($value, $connector->settings['pricing_rules']);
                    }

                    $commonValues[$channelField] = $value;
                }
            }
        }

        return $this->buildPayload($localeValues, $commonValues);
    }

    public function buildPayload(array $localeValues, array $commonValues): array
    {
        return [
            'locales' => $localeValues,
            'common'  => $commonValues,
        ];
    }

    public function computeDataHash(array $payload): string
    {
        $normalized = $this->sortRecursive($payload);
        $hash = hash('xxh128', json_encode($normalized));

        Log::debug('[ChannelConnector] Computed data hash', [
            'hash' => $hash,
        ]);

        return $hash;
    }

    /**
     * Alias for computeDataHash for backward compatibility.
     */
    public function computeHash(array $payload): string
    {
        return $this->computeDataHash($payload);
    }

    public function detectChanges(Product $product, ?ProductChannelMapping $pcMapping, Collection $mappings): bool
    {
        if (! $pcMapping || ! $pcMapping->data_hash) {
            Log::info('[ChannelConnector] Change detected: no existing mapping or hash', [
                'product_id' => $product->id,
            ]);

            return true;
        }

        $currentPayload = $this->prepareSyncPayload($product, $mappings);
        $currentHash = $this->computeDataHash($currentPayload);

        $hasChanges = $currentHash !== $pcMapping->data_hash;

        Log::info('[ChannelConnector] Change detection result', [
            'product_id'   => $product->id,
            'connector_id' => $pcMapping->channel_connector_id,
            'has_changes'  => $hasChanges,
            'current_hash' => $currentHash,
            'stored_hash'  => $pcMapping->data_hash,
        ]);

        return $hasChanges;
    }

    protected function sortRecursive(array $array): array
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = $this->sortRecursive($value);
            }
        }

        return $array;
    }
}

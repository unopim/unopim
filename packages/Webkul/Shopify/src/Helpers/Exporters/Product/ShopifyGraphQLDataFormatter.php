<?php

namespace Webkul\Shopify\Helpers\Exporters\Product;

class ShopifyGraphQLDataFormatter
{
    protected $productIndexes = ['title', 'handle', 'vendor', 'descriptionHtml', 'productType'];

    protected $seoFields = ['metafields_global_title_tag', 'metafields_global_description_tag'];

    protected $variantIndexes = ['inventoryPolicy', 'barcode', 'taxable', 'compareAtPrice', 'sku', 'inventoryTracked', 'cost', 'weight', 'price', 'inventoryQuantity'];

    protected $currency = 'USD';

    protected $locationId = null;

    protected $separators = [
        'colon' => ': ',
        'dash'  => '- ',
        'space' => ' ',
    ];

    protected $settingMapping;

    protected $attributeAll;

    /**
     * Formats raw product data for GraphQL API based on export mapping and other parameters.
     * */
    public function formatDataForGraphql(
        array $rawData,
        array $exportMapping,
        string $locale,
        array $parentData = [],
        $productMetaField = [],
        $variantMetaField = [],
    ): array {
        $status = $this->getStatus($rawData, $parentData);

        $formatted = [
            'title'  => $parentData['sku'] ?? $rawData['sku'],
            'status' => $status,
        ];

        if ($this->locationId) {
            $formatted['variant']['inventoryQuantities']['locationId'] = $this->locationId;
            $formatted['variant']['inventoryQuantities']['availableQuantity'] = 0;
        }

        $formatted = $this->processShopifyConnectorSettings($formatted, $rawData, $exportMapping, $locale, $parentData);
        $formatted = $this->processShopifyConnectorDefaults($formatted, $exportMapping);

        $this->processShopifyMetafieldDefintions($formatted, $rawData, $locale, $parentData, $productMetaField, $variantMetaField, $exportMapping['unit'] ?? []);

        return $formatted;
    }

    public function processShopifyMetafieldDefintions(
        array &$formatted,
        array $rawData,
        ?string $locale,
        array $parentData,
        array $productMetaField,
        array $variantMetaField,
        array $units,
    ): void {
        if (! empty($productMetaField) && ! empty($parentData)) {
            $formatted['parentMetaFields'] = $this->processProductMetaFieldDefintions($parentData, $locale, $productMetaField, $units);
        }

        $metaField = empty($parentData) ? $productMetaField : $variantMetaField;

        $formatted['metafields'] = $this->processProductMetaFieldDefintions($rawData, $locale, $metaField, $units);
    }

    public function processProductMetaFieldDefintions(
        array $rawData,
        ?string $locale,
        array $productMetaField,
        array $units
    ) {
        $formatted = [];
        foreach ($productMetaField as $field) {
            $unoAttribute = $field['code'] ?? null;
            if (! empty($rawData[$unoAttribute] ?? null)) {
                $nameSpaceAndKey = explode('.', $field['name_space_key']);
                if (count($nameSpaceAndKey) > 2) {
                    continue;
                }

                $type = $field['type'] ?? null;
                $attribute = $this->attributeAll[$unoAttribute] ?? null;

                switch ($type) {
                    case 'multi_line_text_field':
                    case 'color':
                        $metafieldValue = $rawData[$unoAttribute] ?? null;
                        break;

                    case 'rating':
                        if (isset($field['validations'])) {
                            $ratingValidation = json_decode($field['validations'], true);
                            $updatedData = array_combine(
                                array_map(fn ($key) => 'scale_' . $key, array_keys($ratingValidation)),
                                $ratingValidation
                            );
                            $updatedData['value'] = $rawData[$unoAttribute] ?? null;
                            $metafieldValue = json_encode($updatedData);
                        }
                        break;

                    case 'weight':
                        $metafieldValue = json_encode([
                            'value' => $rawData[$unoAttribute] ?? null,
                            'unit' => $units['weight'] ?? 'GRAMS'
                        ]);
                        break;

                    case 'volume':
                        $metafieldValue = json_encode([
                            'value' => $rawData[$unoAttribute] ?? null,
                            'unit' => $units['volume'] ?? 'MILLILITERS'
                        ]);
                        break;

                    case 'dimension':
                        $metafieldValue = json_encode([
                            'value' => $rawData[$unoAttribute] ?? null,
                            'unit' => $units['dimension'] ?? 'MILLIMETERS'
                        ]);
                        break;

                    default:
                        $metafieldValue = ($attribute?->type === 'price')
                            ? (($rawData[$unoAttribute] ?? [])[$this->currency] ?? null)
                            : $this->stripTagMetafield($rawData[$unoAttribute] ?? '');
                        break;
                }


                if (! empty($field['listvalue'])) {
                    $type = $field['listvalue'] ? 'list.'.$type : $type;
                    $metafieldValue = $this->formatMetafieldValue($metafieldValue, $attribute, $locale);
                }

                $formatted[] = [
                    'key'       => $nameSpaceAndKey[1],
                    'value'     => $metafieldValue,
                    'type'      => $type,
                    'namespace' => $nameSpaceAndKey[0],
                ];
            }
        }

        return $formatted;
    }

    public function formatMetafieldValue($metafieldValue, $attribute, $locale)
    {
        if (in_array($attribute?->type, ['multiselect', 'select'])) {
            $translateLabels = $this->getTranslatedOptionLabels($attribute, $metafieldValue, $locale);

            return json_encode($translateLabels);
        }

        return json_encode([$metafieldValue]);
    }

    public function isValidHexColor($color)
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color);
    }

    /**
     * Get status of the product
     * */
    protected function getStatus(array $rawData, array $parentData): string
    {
        $status = 'ACTIVE';

        if (! empty($rawData['status']) && $rawData['status'] == 'false') {
            $status = 'DRAFT';
        }

        if (! empty($parentData['status']) && $parentData['status'] == 'false') {
            $status = 'DRAFT';
        }

        if (! empty($parentData['status']) && $parentData['status'] == 'true') {
            $status = 'ACTIVE';
        }

        return $status;
    }

    /**
     * Processes Shopify connector settings and maps fields from raw data to the formatted output.
     * */
    protected function processShopifyConnectorSettings(array $formatted, array $rawData, array $exportMapping, string $locale, array $parentData = [])
    {
        foreach ($exportMapping['shopify_connector_settings'] ?? [] as $shopifyField => $unopimField) {
            if (in_array($shopifyField, $this->productIndexes)) {
                $typeCastValues = $parentData[$unopimField] ?? $rawData[$unopimField] ?? '';
                $attribute = $this->attributeAll[$unopimField] ?? null;
                if ($attribute?->type == 'select') {
                    $option = $attribute->options()->where('code', $typeCastValues)->orderBy('sort_order')->first();
                    $optionTrans = $option?->toArray()['translations'] ?? [];
                    $optionLabelValue = array_values(array_filter($optionTrans, fn ($item) => $item['locale'] === $locale))[0]['label'] ?? null;
                    if (! empty($optionLabelValue)) {
                        $typeCastValues = $optionLabelValue;
                    }
                }
                $formatted[$shopifyField] = (string) $typeCastValues;

                continue;
            }

            if (in_array($shopifyField, $this->seoFields)) {
                $name = $shopifyField === 'metafields_global_title_tag' ? 'title' : 'description';
                $formatted['seo'][$name] = $parentData[$unopimField] ?? $rawData[$unopimField] ?? '';

                continue;
            }

            if (in_array($shopifyField, $this->variantIndexes)) {
                $formatted = $this->processVariantFields($formatted, $rawData, $shopifyField, $unopimField, $exportMapping['unit'] ?? []);

                continue;
            }

            if ($shopifyField == 'tags') {
                $formatted[$shopifyField] = $this->processTags($rawData, $parentData, $unopimField, $locale);

                continue;
            }
        }

        $formatted['variant']['inventoryItem']['sku'] = (string)$rawData['sku'] ?? '';

        return $formatted;
    }

    /**
     * Processes Shopify connector defaults and applies default values to the formatted output.
     *
     * */
    protected function processShopifyConnectorDefaults(array $formatted, array $exportMapping)
    {
        foreach ($exportMapping['shopify_connector_defaults'] ?? [] as $shopifyField => $defaultValue) {
            $formatted = $this->applyDefaultValue($formatted, $shopifyField, $defaultValue);
        }

        return $formatted;
    }

    /**
     * Processes specific variant fields and formats them for Shopify.
     * */
    protected function processVariantFields(
        array $formatted,
        array $rawData,
        string $shopifyField,
        string $unopimField,
        array $units,
    ): array {
        switch ($shopifyField) {
            case 'inventoryPolicy':
                $formatted['variant'][$shopifyField] = ! empty($rawData[$unopimField]) && $rawData[$unopimField] == 'true' ? 'CONTINUE' : 'DENY';

                break;
            case 'barcode':
                $barCode = $rawData[$unopimField] ?? '';
                $formatted['variant'][$shopifyField] = (string) $barCode;

                break;
            case 'taxable':
                $formatted['variant']['taxable'] = ($rawData[$unopimField] ?? null) == 'false' ? false : true;

                break;
            case 'compareAtPrice':
                $formatted['variant']['compareAtPrice'] = (int) ($rawData[$unopimField][$this->currency] ?? 0);

                break;
            case 'sku':
                $skuValues = $rawData[$unopimField] ?? '';
                $formatted['variant']['inventoryItem']['sku'] = (string) $skuValues;

                break;
            case 'inventoryTracked':
                $formatted['variant']['inventoryItem']['tracked'] = ($rawData[$unopimField] ?? null) == 'false' ? false : true;

                break;
            case 'cost':
                $formatted['variant']['inventoryItem']['cost'] = (float) ($rawData[$unopimField][$this->currency] ?? 0);

                break;
            case 'weight':
                $formatted['variant']['inventoryItem']['measurement']['weight'] = [
                    'value' => (float) ($rawData[$unopimField] ?? 0),
                    'unit'  => $units['weight'] ?? 'GRAMS',
                ];

                break;
            case 'price':
                $formatted['variant']['price'] = (float) (($rawData[$unopimField] ?? [])[$this->currency] ?? 0);

                break;
            case 'inventoryQuantity':
                if ($this->locationId) {
                    $formatted['variant']['inventoryQuantities']['availableQuantity'] = (int) ($rawData[$unopimField] ?? 0);
                }

                break;
        }

        return $formatted;
    }

    /**
     * Processes tags based on raw and parent data, Unopim fields, and locale
     */
    protected function processTags(array $rawData, array $parentData, string $unopimField, string $locale): array
    {
        $attributeData = [];

        $unopimAttr = explode(',', $unopimField);

        foreach ($unopimAttr as $attributeCode) {
            $attribute = $this->attributeAll[$attributeCode] ?? null;
            $attributeLabel = empty($attribute?->translate($locale)->name) ? $attribute?->code : $attribute?->translate($locale)->name;
            $value = strip_tags($parentData[$attributeCode] ?? $rawData[$attributeCode] ?? '');
            if (in_array($attribute?->type, ['multiselect', 'select'])) {
                $value = $this->getTranslatedOptionLabels($attribute, $value, $locale);
                $value = implode(' / ', $value);
            }
            if (! $value) {
                continue;
            }

            if (
                isset($this->settingMapping->mapping['enable_tags_attribute'])
                && filter_var($this->settingMapping->mapping['enable_tags_attribute'])
            ) {
                $separators = $this->separators[$this->settingMapping->mapping['tagSeprator']] ?? ':';
                $attributeData[] = $attributeLabel.$separators.$value;

                continue;
            }

            if ($this->settingMapping->mapping['enable_named_tags_attribute'] ?? false) {
                $attributeData[] = $attributeLabel.':'.$attribute?->type.':'.$value;

                continue;
            }

            $attributeData[] = $value;
        }

        return $attributeData;
    }

    /**
     * Get option label from option code
     */
    protected function getTranslatedOptionLabels($attribute, $value, string $locale)
    {
        $values = explode(',', $value);
        $optionTrans = $attribute->options()->whereIn('code', $values)->get()->toArray();
        $translationsArray = array_column($optionTrans, 'translations');
        $translateLabels = array_map(function ($translations, $index) use ($locale, $values) {
            $labelArr = array_column(array_filter($translations, fn ($t) => $t['locale'] === $locale), 'label');
            $label = $labelArr[0] ?? null;

            return ! empty($label) ? $label : $values[$index];
        }, $translationsArray, array_keys($translationsArray));

        return $translateLabels;
    }

    /**
     * Applies default values to the formatted data for Shopify fields.
     */
    protected function applyDefaultValue(array $formatted, string $shopifyField, $defaultValue): array
    {
        $defaultValue = $defaultValue ?? '';

        if (in_array($shopifyField, $this->productIndexes)) {
            $formatted[$shopifyField] = $defaultValue;
        } elseif (in_array($shopifyField, $this->seoFields)) {
            $name = $shopifyField === 'metafields_global_title_tag' ? 'title' : 'description';
            $formatted['seo'][$name] = $defaultValue;
        } elseif (in_array($shopifyField, $this->variantIndexes)) {
            $formatted = $this->applyDefaultVariantValue($formatted, $shopifyField, $defaultValue);
        } elseif ($shopifyField == 'tags') {
            $formatted[$shopifyField] = $defaultValue;
        }

        return $formatted;
    }

    /**
     * Applies default values to Shopify variant fields.
     */
    protected function applyDefaultVariantValue(array $formatted, string $shopifyField, string $defaultValue): array
    {
        switch ($shopifyField) {
            case 'inventoryPolicy':
                $formatted['variant'][$shopifyField] = $defaultValue && strtolower($defaultValue) == 'true' ? 'CONTINUE' : 'DENY';
                break;
            case 'barcode':
                $formatted['variant'][$shopifyField] = (string) $defaultValue;
                break;
            case 'price':
                $formatted['variant'][$shopifyField] = (float) $defaultValue;
                break;
            case 'taxable':
                $formatted['variant']['taxable'] = $defaultValue && strtolower($defaultValue) == 'true' ? true : false;
                break;
            case 'inventoryTracked':
                $formatted['variant']['inventoryItem']['tracked'] = $defaultValue && strtolower($defaultValue) == 'true' ? true : false;
                break;
            case 'compareAtPrice':
                $formatted['variant']['compareAtPrice'] = (int) $defaultValue;
                break;
            case 'inventoryQuantity':
                if ($this->locationId) {
                    $formatted['variant']['inventoryQuantities']['availableQuantity'] = (int) $defaultValue;
                }
                break;
            case 'sku':
                $formatted['variant']['inventoryItem']['sku'] = (string)$defaultValue;
                break;
            case 'cost':
                $formatted['variant']['inventoryItem']['cost'] = (float) $defaultValue;
                break;
            case 'weight':
                $formatted['variant']['inventoryItem']['measurement']['weight'] = [
                    'value' => (float) $defaultValue,
                    'unit'  => 'GRAMS',
                ];
                break;
        }

        return $formatted;
    }

    /**
     * striptag metafields value remove html entities and code and new line
     */
    protected function stripTagMetafield(string $metafieldValue): string
    {
        $metafieldValue = strip_tags($metafieldValue);
        $metafieldValue = preg_replace('/&#?[a-z0-9]{2,8};/i', '', $metafieldValue);
        $metafieldValue = str_replace(["\r\n", "\r", "\n"], PHP_EOL, $metafieldValue);
        $metafieldValue = preg_replace('/\s+/', ' ', $metafieldValue);

        return $metafieldValue;
    }

    /**
     * Sets the initial data for the class properties.
     */
    public function setInitialData(string $locationId, string $currency, $settings, $attributeAll)
    {
        $this->locationId = $locationId;
        $this->currency = $currency;
        $this->settingMapping = $settings;
        $this->attributeAll = $attributeAll;
    }
}

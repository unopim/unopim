<?php

namespace Webkul\AiAgent\Services;

use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Persists an AI-generated product draft into the Unopim PIM.
 *
 * Dynamically resolves attribute placement (common, channel_specific,
 * channel_locale_specific) from the product's attribute family rather
 * than relying on static constants — so custom attributes are supported.
 */
class ProductWriterService
{
    /**
     * Attribute types that require multi-currency object values.
     */
    protected const PRICE_TYPE = 'price';

    /**
     * Attribute types that require option code resolution for select/multiselect.
     */
    protected const SELECT_TYPES = ['select', 'multiselect'];

    /**
     * System attributes handled specially (not from AI data).
     */
    protected const SYSTEM_ATTRS = ['sku', 'url_key', 'image'];

    /**
     * Create a product in the PIM from the enriched context.
     *
     * @param  array<string, mixed>  $options
     */
    public function createProduct(ImageProductContext $ctx, array $options = []): ImageProductContext
    {
        $sku = $options['sku'] ?? $this->generateSku($ctx);
        $locale = $options['locale'] ?? 'en_US';
        $channel = $options['channel'] ?? 'default';
        $family = $options['family'] ?? null;

        $resolved = $ctx->resolvedAttributes();

        $productId = $this->createViaRepository($sku, $family, $resolved, $locale, $channel, $ctx);

        return $ctx->withProductId($productId);
    }

    /**
     * Create a product via Unopim's ProductRepository.
     *
     * @param  array<string, mixed>  $attributes  Merged vision + enrichment attributes
     */
    protected function createViaRepository(
        string $sku,
        ?string $family,
        array $attributes,
        string $locale,
        string $channel,
        ImageProductContext $ctx,
    ): int|string {
        /** @var ProductRepository $repo */
        $repo = app('Webkul\Product\Repositories\ProductRepository');

        $familyId = $this->resolveFamily($family);

        // 1 — Create the bare product
        $product = $repo->create([
            'sku'                 => $sku,
            'type'                => 'simple',
            'attribute_family_id' => $familyId,
        ]);

        // 2 — Load all attributes belonging to this family with their metadata
        $familyAttributes = $this->getFamilyAttributes($familyId);
        $currencies = $this->getActiveCurrencies();

        // Build a name from AI data
        $productName = $attributes['detected_name'] ?? $attributes['name'] ?? $sku;
        $urlKey = Str::slug($productName);

        // Initialize value buckets
        $commonValues = ['sku' => $sku, 'url_key' => $urlKey];
        $channelLocaleValues = [];
        $channelSpecificValues = [];
        $localeSpecificValues = [];

        // Auto-set product_number from SKU if the family has it
        if (isset($familyAttributes['product_number']) && empty($attributes['product_number'])) {
            $attributes['product_number'] = $sku;
        }

        // 3 — Route each AI attribute to the correct value bucket based on family metadata
        foreach ($familyAttributes as $attrCode => $attrMeta) {
            if (\in_array($attrCode, self::SYSTEM_ATTRS, true)) {
                continue;
            }

            $value = $attributes[$attrCode] ?? null;

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            // Handle price-type attributes (multi-currency object)
            if ($attrMeta['type'] === self::PRICE_TYPE) {
                $value = $this->buildPriceValue($value, $currencies);
            }

            // Handle select-type attributes (resolve to option code)
            if (\in_array($attrMeta['type'], self::SELECT_TYPES, true)) {
                $value = $this->resolveSelectValue($attrCode, $value, $attrMeta['attribute_id']);
                if ($value === null) {
                    continue;
                }
            }

            // Place the value in the correct bucket based on value_per_channel/value_per_locale
            if ($attrMeta['value_per_channel'] && $attrMeta['value_per_locale']) {
                $channelLocaleValues[$attrCode] = $value;
            } elseif ($attrMeta['value_per_channel']) {
                $channelSpecificValues[$attrCode] = $value;
            } elseif ($attrMeta['value_per_locale']) {
                $localeSpecificValues[$attrCode] = $value;
            } else {
                $commonValues[$attrCode] = $value;
            }
        }

        // Handle estimated_price → price attribute (if not already set)
        $estimatedPrice = $attributes['estimated_price'] ?? null;

        if ($estimatedPrice && is_numeric($estimatedPrice) && isset($familyAttributes['price']) && ! isset($channelLocaleValues['price'])) {
            $channelLocaleValues['price'] = $this->buildPriceValue($estimatedPrice, $currencies);
        }

        // Estimate cost from price (~60% of retail) if cost attribute exists and not set
        if ($estimatedPrice && is_numeric($estimatedPrice) && isset($familyAttributes['cost']) && ! isset($channelSpecificValues['cost'])) {
            $channelSpecificValues['cost'] = $this->buildPriceValue(
                round((float) $estimatedPrice * 0.6, 2),
                $currencies,
            );
        }

        // 4 — Assemble the full values JSON structure
        $values = $product->values ?? [];
        $values['common'] = array_merge($values['common'] ?? [], $commonValues);

        if (! empty($channelLocaleValues)) {
            $values['channel_locale_specific'][$channel][$locale] = array_merge(
                $values['channel_locale_specific'][$channel][$locale] ?? [],
                $channelLocaleValues,
            );
        }

        if (! empty($channelSpecificValues)) {
            $values['channel_specific'][$channel] = array_merge(
                $values['channel_specific'][$channel] ?? [],
                $channelSpecificValues,
            );
        }

        if (! empty($localeSpecificValues)) {
            $values['locale_specific'][$locale] = array_merge(
                $values['locale_specific'][$locale] ?? [],
                $localeSpecificValues,
            );
        }

        // 5 — Save categories
        $categoryValues = $this->resolveCategories($ctx->category, $attributes['categories'] ?? null);

        if (! empty($categoryValues)) {
            $values['categories'] = $categoryValues;
        }

        // 6 — Attach the uploaded image to the product
        $imagePath = $this->storeProductImage($product->id, $ctx->imagePath);

        if ($imagePath) {
            $values['common']['image'] = $imagePath;
        }

        $product->values = $values;
        $product->save();

        // 7 — Log execution
        $this->logExecution($product->id, $ctx, $sku, $locale, $channel);

        return $product->id;
    }

    // -------------------------------------------------------------------------
    // Attribute family resolution
    // -------------------------------------------------------------------------

    /**
     * Load all attributes for an attribute family with their metadata.
     *
     * Returns: [attr_code => [type, value_per_locale, value_per_channel, attribute_id]]
     *
     * @return array<string, array{type: string, value_per_locale: bool, value_per_channel: bool, attribute_id: int}>
     */
    protected function getFamilyAttributes(int $familyId): array
    {
        $rows = DB::table('attributes as a')
            ->join('attribute_group_mappings as agm', 'agm.attribute_id', '=', 'a.id')
            ->join('attribute_family_group_mappings as afgm', 'afgm.id', '=', 'agm.attribute_family_group_id')
            ->where('afgm.attribute_family_id', $familyId)
            ->select('a.id as attribute_id', 'a.code', 'a.type', 'a.value_per_locale', 'a.value_per_channel')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[$row->code] = [
                'type'              => $row->type,
                'value_per_locale'  => (bool) $row->value_per_locale,
                'value_per_channel' => (bool) $row->value_per_channel,
                'attribute_id'      => $row->attribute_id,
            ];
        }

        return $map;
    }

    /**
     * Build a multi-currency price/cost value object.
     *
     * @param  mixed  $value  Numeric value or existing price object
     * @param  array<string>  $currencies
     * @return array<string, string>
     */
    protected function buildPriceValue(mixed $value, array $currencies): array
    {
        // If already a multi-currency object, return as-is
        if (is_array($value)) {
            return $value;
        }

        if (! is_numeric($value)) {
            return [];
        }

        $priceObj = [];

        foreach ($currencies as $currencyCode) {
            $priceObj[$currencyCode] = (string) round((float) $value, 2);
        }

        return $priceObj;
    }

    /**
     * Resolve a text value from AI to a valid option code for select/multiselect attributes.
     *
     * @return string|null The matched option code, or null if no match
     */
    protected function resolveSelectValue(string $attrCode, mixed $value, int $attributeId): ?string
    {
        if (! is_string($value) || empty($value)) {
            return null;
        }

        // Load available options for this attribute
        $options = DB::table('attribute_options as ao')
            ->leftJoin('attribute_option_translations as aot', function ($join) {
                $join->on('aot.attribute_option_id', '=', 'ao.id')
                    ->where('aot.locale', '=', 'en_US');
            })
            ->where('ao.attribute_id', $attributeId)
            ->select('ao.code', 'aot.label')
            ->get();

        // Try exact code match first
        foreach ($options as $opt) {
            if (strcasecmp($opt->code, $value) === 0) {
                return $opt->code;
            }
        }

        // Try label match
        foreach ($options as $opt) {
            if ($opt->label && strcasecmp($opt->label, $value) === 0) {
                return $opt->code;
            }
        }

        // Try partial/fuzzy match (AI might return "Stainless Steel" for a "Steel" option)
        $valueLower = strtolower($value);

        foreach ($options as $opt) {
            $codeLower = strtolower($opt->code);
            $labelLower = $opt->label ? strtolower($opt->label) : '';

            if (str_contains($valueLower, $codeLower) || str_contains($codeLower, $valueLower)) {
                return $opt->code;
            }

            if ($labelLower && (str_contains($valueLower, $labelLower) || str_contains($labelLower, $valueLower))) {
                return $opt->code;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Log the AI execution to ai_agent_executions.
     */
    protected function logExecution(
        int|string $productId,
        ImageProductContext $ctx,
        string $sku,
        string $locale,
        string $channel,
    ): void {
        DB::table('ai_agent_executions')->insert([
            'agentId'         => null,
            'credentialId'    => null,
            'status'          => $ctx->requiresReview() ? 'pending_review' : 'completed',
            'instruction'     => json_encode(['sku' => $sku, 'image' => $ctx->imagePath]),
            'output'          => json_encode($ctx->resolvedAttributes()),
            'tokensUsed'      => 0,
            'executionTimeMs' => 0,
            'error'           => null,
            'extras'          => json_encode([
                'product_id'        => $productId,
                'source'            => 'ai_image_pipeline',
                'locale'            => $locale,
                'channel'           => $channel,
                'detected_product'  => $ctx->detectedProduct,
                'category'          => $ctx->category,
                'confidence'        => $ctx->confidence,
                'enrichment'        => $ctx->enrichment,
                'requires_review'   => $ctx->requiresReview(),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Store the uploaded image into UnoPim's product storage path.
     *
     * @return string|null The relative storage path, or null on failure
     */
    protected function storeProductImage(int|string $productId, ?string $imagePath): ?string
    {
        if (! $imagePath || ! file_exists($imagePath)) {
            return null;
        }

        try {
            $fileStorer = app(FileStorer::class);
            $storagePath = 'product'.DIRECTORY_SEPARATOR.$productId.DIRECTORY_SEPARATOR.'image';

            return $fileStorer->store(
                $storagePath,
                new File($imagePath),
                [FileStorer::HASHED_FOLDER_NAME_KEY => true],
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get active currency codes from the database.
     *
     * @return array<string>
     */
    protected function getActiveCurrencies(): array
    {
        return DB::table('currencies')
            ->where('status', 1)
            ->pluck('code')
            ->toArray() ?: ['USD'];
    }

    /**
     * Resolve AI-suggested categories to actual category codes in the PIM.
     *
     * @return array<string>
     */
    protected function resolveCategories(?string $primaryCategory, mixed $categoriesAttr): array
    {
        $suggestions = [];

        if ($primaryCategory) {
            $suggestions[] = $primaryCategory;
        }

        if (is_array($categoriesAttr)) {
            $suggestions = array_merge($suggestions, $categoriesAttr);
        } elseif (is_string($categoriesAttr) && ! empty($categoriesAttr)) {
            $suggestions[] = $categoriesAttr;
        }

        if (empty($suggestions)) {
            return [];
        }

        $allCategories = DB::table('categories')->pluck('code')->toArray();
        $matched = [];

        foreach ($suggestions as $suggestion) {
            if (! is_string($suggestion)) {
                continue;
            }

            // Direct code match
            if (\in_array($suggestion, $allCategories, true)) {
                $matched[] = $suggestion;

                continue;
            }

            // Try matching the last segment of "Electronics > Laptops" paths
            $segments = array_map('trim', explode('>', $suggestion));
            $lastSegment = end($segments);
            $slugged = Str::slug($lastSegment);

            foreach ($allCategories as $code) {
                if (strcasecmp($code, $lastSegment) === 0 || $code === $slugged) {
                    $matched[] = $code;
                    break;
                }
            }
        }

        return array_unique($matched);
    }

    /**
     * Generate a SKU from the context.
     */
    protected function generateSku(ImageProductContext $ctx): string
    {
        $base = $ctx->detectedProduct ?? $ctx->attributes['product_type'] ?? 'product';
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $base) ?? 'product');
        $slug = trim($slug, '-');

        return substr($slug, 0, 40).'-'.strtoupper(substr(md5(uniqid('', true)), 0, 6));
    }

    /**
     * Resolve attribute family ID from a code or use default.
     */
    protected function resolveFamily(?string $family): int
    {
        if ($family && app()->bound('Webkul\Attribute\Repositories\AttributeFamilyRepository')) {
            $repo = app('Webkul\Attribute\Repositories\AttributeFamilyRepository');
            $model = $repo->findOneByField('code', $family);

            if ($model) {
                return $model->id;
            }
        }

        // Fallback: use the first attribute family
        return DB::table('attribute_families')->value('id') ?? 1;
    }

    // -------------------------------------------------------------------------
    // Public accessors for Chat Tool classes
    // -------------------------------------------------------------------------

    /**
     * Public accessor for getFamilyAttributes.
     *
     * @return array<string, array{type: string, value_per_locale: bool, value_per_channel: bool, attribute_id: int}>
     */
    public function getFamilyAttributesPublic(int $familyId): array
    {
        return $this->getFamilyAttributes($familyId);
    }

    /**
     * Public accessor for resolveSelectValue.
     */
    public function resolveSelectValuePublic(string $attrCode, mixed $value, int $attributeId): ?string
    {
        return $this->resolveSelectValue($attrCode, $value, $attributeId);
    }
}

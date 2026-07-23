<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Concerns\QueuesForApproval;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Jobs\TranslateProductValuesJob;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Product\Repositories\ProductRepository;

class CreateProduct implements PimTool
{
    public function __construct(
        protected ProductWriterService $writerService,
    ) {}

    public function register(ChatContext $context): Tool
    {
        $writerService = $this->writerService;

        return new class($context, $writerService) extends ContextualTool
        {
            use ChecksPermission;
            use QueuesForApproval;

            public function __construct(ChatContext $context, protected ProductWriterService $writerService)
            {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'create_product';
            }

            public function description(): string
            {
                return 'Create a product with attributes, categories, and image. Supports simple and configurable (with variants) product types. Pass all attributes in attributes_json. Pass family to choose the attribute family; otherwise the current product context or the configured default family is used. For configurable products, provide super_attributes and variants_json.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'sku'               => $schema->string()->description('Product SKU (must be unique). Auto-generated if not provided.'),
                    'name'              => $schema->string()->description('Product name (required)'),
                    'description'       => $schema->string()->description('Product description'),
                    'short_description' => $schema->string()->description('Short product description'),
                    'meta_title'        => $schema->string()->description('SEO meta title'),
                    'meta_description'  => $schema->string()->description('SEO meta description'),
                    'meta_keywords'     => $schema->string()->description('SEO meta keywords'),
                    'categories'        => $schema->string()->description('Comma-separated category codes or paths to assign'),
                    'family'            => $schema->string()->description('Attribute family code for the product. Defaults to the family of the product being edited, then the configured default family, then the first family.'),
                    'attach_image'      => $schema->boolean()->description('Set to true to attach the uploaded image (default: true)'),
                    'attributes_json'   => $schema->string()->description('JSON string of ALL additional attribute values including color, size, brand, price, cost, product_number, etc.'),
                    'product_type'      => $schema->string()->description('Product type: "simple" (default) or "configurable". Use configurable when the product has variants like different colors/sizes.'),
                    'super_attributes'  => $schema->string()->description('For configurable products only: comma-separated attribute codes that define variants (e.g. "color,size")'),
                    'variants_json'     => $schema->string()->description('For configurable products only: JSON array of variant objects. Each variant needs a unique combo of super attribute values. Example: [{"sku":"PROD-RED-S","color":"Red","size":"S","price":49.99},{"sku":"PROD-RED-M","color":"Red","size":"M","price":49.99}]'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.create')) {
                    return $denied;
                }

                $sku = $request->string('sku')->toString() ?: null;
                $name = $request->string('name')->toString() ?: null;
                $description = $request->string('description')->toString() ?: null;
                $short_description = $request->string('short_description')->toString() ?: null;
                $meta_title = $request->string('meta_title')->toString() ?: null;
                $meta_description = $request->string('meta_description')->toString() ?: null;
                $meta_keywords = $request->string('meta_keywords')->toString() ?: null;
                $categories = $request->string('categories')->toString() ?: null;
                $family = $request->string('family')->toString() ?: null;
                $attach_image = $request->has('attach_image') ? $request->boolean('attach_image') : true;
                $attributes_json = $request->string('attributes_json')->toString() ?: null;
                $product_type = $request->string('product_type')->toString() ?: 'simple';
                $super_attributes = $request->string('super_attributes')->toString() ?: null;
                $variants_json = $request->string('variants_json')->toString() ?: null;

                $extraAttrs = $attributes_json ? (json_decode($attributes_json, true) ?? []) : [];

                if (! $name && ! empty($extraAttrs['detected_name'])) {
                    $name = $extraAttrs['detected_name'];
                }

                if (! $name && ! empty($extraAttrs['name'])) {
                    $name = $extraAttrs['name'];
                }

                if (! $name) {
                    return json_encode(['error' => 'Product name is required']);
                }

                // Determine product type
                $type = ($product_type === 'configurable' || ! empty($super_attributes)) ? 'configurable' : 'simple';

                // Merge all attributes
                $allAttrs = array_filter(array_merge($extraAttrs, [
                    'name'              => $name,
                    'description'       => $description,
                    'short_description' => $short_description,
                    'meta_title'        => $meta_title,
                    'meta_description'  => $meta_description,
                    'meta_keywords'     => $meta_keywords,
                ]), fn ($v): bool => $v !== null && $v !== '');

                $sku = $sku ?: Str::slug($name).'-'.strtoupper(Str::random(6));

                if (DB::table('products')->where('sku', $sku)->exists()) {
                    return json_encode(['error' => "SKU '{$sku}' already exists"]);
                }

                $familyResolution = $this->resolveFamilyId($family);

                if (isset($familyResolution['error'])) {
                    return json_encode($familyResolution);
                }

                $familyId = $familyResolution['id'];

                if ($this->shouldQueueForApproval()) {
                    return $this->queueChange($this->context, "Create {$type} product: {$name}", [
                        'type'           => 'create_product',
                        'data'           => ['sku' => $sku, 'name' => $name, 'product_type' => $type, 'family' => $family, 'attributes' => $allAttrs],
                        'affected_count' => 1,
                    ]);
                }

                $repo = resolve(ProductRepository::class);

                // Create the product (simple or configurable parent)
                if ($type === 'configurable' && $super_attributes) {
                    $superAttrCodes = array_map(trim(...), explode(',', $super_attributes));

                    $product = $repo->create([
                        'sku'                 => $sku,
                        'type'                => 'configurable',
                        'attribute_family_id' => $familyId,
                        'super_attributes'    => $superAttrCodes,
                    ]);
                } else {
                    $product = $repo->create([
                        'sku'                 => $sku,
                        'type'                => 'simple',
                        'attribute_family_id' => $familyId,
                    ]);
                }

                // Load family attributes for dynamic routing
                $familyAttributes = $this->writerService->getFamilyAttributesPublic($familyId);

                // Handle estimated_price → price mapping
                if (! empty($allAttrs['estimated_price']) && empty($allAttrs['price'])) {
                    $allAttrs['price'] = $allAttrs['estimated_price'];
                }
                unset($allAttrs['estimated_price']);

                // Price/cost defaults only apply when the family carries those attributes
                if (isset($familyAttributes['price'])) {
                    // Price defaults to 0 — signals "price not set" to the admin
                    if (empty($allAttrs['price']) || ! is_numeric($allAttrs['price'])) {
                        $allAttrs['price'] = 0;
                    }

                    if (isset($familyAttributes['cost'])) {
                        if ((float) $allAttrs['price'] > 0 && empty($allAttrs['cost'])) {
                            $allAttrs['cost'] = round((float) $allAttrs['price'] * $this->writerService->costPriceRatio(), 2);
                        } elseif (empty($allAttrs['cost'])) {
                            $allAttrs['cost'] = 0;
                        }
                    }
                }

                // Auto-set product_number from SKU if the family has it
                if (isset($familyAttributes['product_number']) && empty($allAttrs['product_number'])) {
                    $allAttrs['product_number'] = $sku;
                }

                // Extract categories
                $categoryValues = null;
                if (! empty($allAttrs['categories'])) {
                    $categoryValues = $allAttrs['categories'];
                }
                unset($allAttrs['categories'], $allAttrs['detected_name'], $allAttrs['product_type']);

                $currencies = $this->writerService->getActiveCurrencyCodes();

                // Get ALL channels and their locales for multi-channel/locale filling
                $allChannels = core()->getAllChannels();

                $values = $product->values ?? [];
                $values['common']['sku'] = $sku;
                $values['common']['url_key'] = Str::slug($name);

                $skippedAttrs = [];
                $translatableFields = []; // Collect text fields for translation

                foreach ($allAttrs as $code => $value) {
                    if (\in_array($code, ['sku', 'url_key', 'image'], true)) {
                        continue;
                    }

                    if (! isset($familyAttributes[$code])) {
                        $skippedAttrs[] = $code;

                        continue;
                    }

                    $meta = $familyAttributes[$code];

                    // Handle price type → multi-currency object
                    if ($meta['type'] === 'price' && is_numeric($value)) {
                        $priceObj = [];
                        foreach ($currencies as $curr) {
                            $priceObj[$curr] = (string) round((float) $value, 2);
                        }
                        $value = $priceObj;
                    }

                    // Handle select/multiselect → resolve to option code
                    if (\in_array($meta['type'], ['select', 'multiselect'], true) && is_string($value)) {
                        $resolved = $this->writerService->resolveSelectValuePublic($code, $value, $meta['attribute_id']);
                        if ($resolved === null) {
                            $skippedAttrs[] = "{$code} (no matching option for '{$value}')";

                            continue;
                        }
                        $value = $resolved;
                    }

                    // Route to correct bucket — source locale/channel only
                    // Non-locale fields (price, select) go to ALL channels since they're not translatable
                    if ($meta['value_per_channel'] && $meta['value_per_locale']) {
                        // Text fields: source locale only, other locales via translation job
                        foreach ($allChannels as $channel) {
                            $values['channel_locale_specific'][$channel->code][$this->context->locale][$code] = $value;
                        }

                        // Track translatable text fields for auto-translation
                        if (\in_array($meta['type'], ['text', 'textarea'], true) && is_string($value)) {
                            $translatableFields[$code] = $value;
                        }
                    } elseif ($meta['value_per_channel']) {
                        // Channel-specific (not locale-dependent, e.g. price) → fill all channels
                        foreach ($allChannels as $channel) {
                            $values['channel_specific'][$channel->code][$code] = $value;
                        }
                    } elseif ($meta['value_per_locale']) {
                        // Locale-specific: source locale only, translate the rest
                        $values['locale_specific'][$this->context->locale][$code] = $value;

                        if (\in_array($meta['type'], ['text', 'textarea'], true) && is_string($value)) {
                            $translatableFields[$code] = $value;
                        }
                    } else {
                        $values['common'][$code] = $value;
                    }
                }

                // Assign categories
                if ($categories || (is_array($categoryValues) && $categoryValues !== [])) {
                    $catInputs = [];

                    if ($categories) {
                        $catInputs = array_map(trim(...), explode(',', $categories));
                    }

                    if (is_array($categoryValues)) {
                        $catInputs = array_merge($catInputs, $categoryValues);
                    }

                    $candidates = [];

                    foreach ($catInputs as $input) {
                        if (! is_string($input)) {
                            continue;
                        }
                        $candidates[] = $input;
                        $segments = array_map(trim(...), explode('>', $input));
                        $last = end($segments);
                        $candidates[] = $last;
                        $candidates[] = Str::slug($last);
                    }

                    $candidates = array_unique(array_filter($candidates));

                    $matched = DB::table('categories')
                        ->whereIn('code', $candidates)
                        ->pluck('code')
                        ->toArray();

                    if (! empty($matched)) {
                        $values['categories'] = array_values(array_unique($matched));
                    }
                }

                // Attach uploaded image
                $imageAttachError = null;

                if ($attach_image && $this->context->hasImages()) {
                    $imagePath = $this->context->firstImagePath();

                    if ($imagePath && file_exists($imagePath)) {
                        try {
                            $fileStorer = resolve(FileStorer::class);
                            $storagePath = 'product'.DIRECTORY_SEPARATOR.$product->id.DIRECTORY_SEPARATOR.'image';

                            $storedImage = $fileStorer->store(
                                $storagePath,
                                new File($imagePath),
                                [FileStorer::HASHED_FOLDER_NAME_KEY => true],
                            );

                            if ($storedImage) {
                                $values['common']['image'] = $storedImage;
                            } else {
                                $imageAttachError = 'FileStorer returned empty path';
                            }
                        } catch (\Throwable $e) {
                            $imageAttachError = $e->getMessage();
                            \Log::warning('CreateProduct: Image attach failed', [
                                'product_id' => $product->id,
                                'image_path' => $imagePath,
                                'error'      => $e->getMessage(),
                            ]);
                        }
                    } else {
                        $imageAttachError = $imagePath
                            ? "Image file not found at: {$imagePath}"
                            : 'No image path available in context';
                    }
                } elseif ($attach_image && ! $this->context->hasImages()) {
                    $imageAttachError = 'No images were uploaded with this request';
                }

                $product->values = $values;
                $product->save();

                // Create variants for configurable products
                $variantsCreated = 0;

                if ($type === 'configurable' && $variants_json) {
                    $variants = json_decode($variants_json, true) ?? [];

                    if (! empty($variants) && $super_attributes) {
                        $superAttrCodes = array_map(trim(...), explode(',', $super_attributes));
                        $superAttrs = $product->super_attributes;

                        foreach ($variants as $variantData) {
                            $variantSku = $variantData['sku'] ?? ($sku.'-'.strtoupper(Str::random(4)));

                            if (DB::table('products')->where('sku', $variantSku)->exists()) {
                                continue;
                            }

                            // Build variant values with super attribute values in common
                            $variantValues = $values;
                            $variantValues['common']['sku'] = $variantSku;
                            $variantValues['common']['url_key'] = Str::slug($variantSku);

                            foreach ($superAttrCodes as $saCode) {
                                if (isset($variantData[$saCode]) && isset($familyAttributes[$saCode])) {
                                    $saValue = $variantData[$saCode];
                                    $saMeta = $familyAttributes[$saCode];

                                    // Resolve select options for super attributes
                                    if (\in_array($saMeta['type'], ['select', 'multiselect'], true) && is_string($saValue)) {
                                        $resolved = $this->writerService->resolveSelectValuePublic($saCode, $saValue, $saMeta['attribute_id']);
                                        if ($resolved !== null) {
                                            $saValue = $resolved;
                                        }
                                    }

                                    $variantValues['common'][$saCode] = $saValue;
                                }
                            }

                            // Override variant-specific attributes (price, etc.)
                            foreach ($variantData as $vCode => $vValue) {
                                if ($vCode === 'sku') {
                                    continue;
                                }
                                if (\in_array($vCode, $superAttrCodes, true)) {
                                    continue;
                                }
                                if (isset($familyAttributes[$vCode])) {
                                    $vMeta = $familyAttributes[$vCode];

                                    if ($vMeta['type'] === 'price' && is_numeric($vValue)) {
                                        $priceObj = [];
                                        foreach ($currencies as $curr) {
                                            $priceObj[$curr] = (string) round((float) $vValue, 2);
                                        }
                                        $vValue = $priceObj;
                                    }

                                    // Route to the correct bucket for all channels/locales
                                    if ($vMeta['value_per_channel'] && $vMeta['value_per_locale']) {
                                        foreach ($allChannels as $channel) {
                                            foreach ($channel->locales as $locale) {
                                                $variantValues['channel_locale_specific'][$channel->code][$locale->code][$vCode] = $vValue;
                                            }
                                        }
                                    } elseif ($vMeta['value_per_channel']) {
                                        foreach ($allChannels as $channel) {
                                            $variantValues['channel_specific'][$channel->code][$vCode] = $vValue;
                                        }
                                    } elseif ($vMeta['value_per_locale']) {
                                        foreach (core()->getAllActiveLocales() as $locale) {
                                            $variantValues['locale_specific'][$locale->code][$vCode] = $vValue;
                                        }
                                    } else {
                                        $variantValues['common'][$vCode] = $vValue;
                                    }
                                }
                            }

                            // Create the variant as a child product
                            $variant = $repo->getModel()->create([
                                'parent_id'           => $product->id,
                                'type'                => 'simple',
                                'attribute_family_id' => $familyId,
                                'sku'                 => $variantSku,
                            ]);

                            $variant->values = $variantValues;
                            $variant->save();
                            $variantsCreated++;
                        }
                    }
                }

                // Dispatch async translation for text fields to other locales
                if ($translatableFields !== []) {
                    dispatch(new TranslateProductValuesJob(productId: $product->id, sourceLocale: $this->context->locale, fieldsToTranslate: $translatableFields, channel: $this->context->channel))->delay(now()->addSeconds(3));
                }

                $productUrl = route('admin.catalog.products.edit', $product->id);

                // Collect all filled attributes across all channels/locales
                $filledAttrs = array_keys($values['common'] ?? []);
                foreach ($values['channel_locale_specific'] ?? [] as $locales) {
                    foreach ($locales as $attrs) {
                        $filledAttrs = array_merge($filledAttrs, array_keys($attrs));
                    }
                }
                $filledAttrs = array_values(array_unique($filledAttrs));

                $result = [
                    'product_id'  => $product->id,
                    'sku'         => $sku,
                    'product_url' => $productUrl,
                    'result'      => [
                        'created'          => true,
                        'sku'              => $sku,
                        'type'             => $type,
                        'filled'           => $filledAttrs,
                        'categories'       => $values['categories'] ?? [],
                        'has_image'        => ! empty($values['common']['image']),
                        'image_error'      => $imageAttachError,
                        'skipped'          => $skippedAttrs === [] ? null : $skippedAttrs,
                        'auto_translating' => $translatableFields !== [],
                    ],
                ];

                if ($type === 'configurable') {
                    $result['result']['variants_created'] = $variantsCreated;
                    $result['result']['super_attributes'] = array_map(trim(...), explode(',', $super_attributes ?? ''));
                }

                return json_encode($result);
            }

            /**
             * Resolve the attribute family id: explicit code, then the product
             * context's family, then the configured default, then the first family.
             *
             * @return array{id?: int, error?: string, available_families?: array<string>}
             */
            protected function resolveFamilyId(?string $familyCode): array
            {
                if ($familyCode !== null) {
                    if (! preg_match('/^[a-zA-Z0-9_-]+$/', $familyCode)) {
                        return ['error' => 'Invalid family: only letters, numbers, underscores and hyphens are allowed.'];
                    }

                    $familyId = DB::table('attribute_families')->where('code', $familyCode)->value('id');

                    if (! $familyId) {
                        return [
                            'error'              => "Attribute family not found: {$familyCode}",
                            'available_families' => DB::table('attribute_families')->orderBy('code')->limit(20)->pluck('code')->toArray(),
                        ];
                    }

                    return ['id' => (int) $familyId];
                }

                if ($this->context->hasProductContext()) {
                    $familyId = DB::table('products')->where('id', $this->context->productId)->value('attribute_family_id');

                    if ($familyId) {
                        return ['id' => (int) $familyId];
                    }
                }

                $defaultFamilyCode = core()->getConfigData('general.magic_ai.agentic_pim.default_family');

                if ($defaultFamilyCode && core()->isValidScopeCode($defaultFamilyCode)) {
                    $familyId = DB::table('attribute_families')->where('code', $defaultFamilyCode)->value('id');

                    if ($familyId) {
                        return ['id' => (int) $familyId];
                    }
                }

                return ['id' => (int) (DB::table('attribute_families')->value('id') ?? 1)];
            }
        };
    }
}

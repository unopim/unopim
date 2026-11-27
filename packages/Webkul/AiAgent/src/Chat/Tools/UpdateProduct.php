<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Jobs\TranslateProductValuesJob;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Repositories\ProductRepository;

class UpdateProduct implements PimTool
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

            public function __construct(
                ChatContext $context,
                protected ProductWriterService $writerService,
            ) {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'update_product';
            }

            public function description(): string
            {
                return 'Update product attributes by SKU. Pass changes as JSON in changes_json. '
                    .'For attributes that hold a value per locale (name, description, ...), pass "locale" '
                    .'to choose which locale is written; without it the conversation locale is used.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'sku'          => $schema->string()->description('Product SKU to update (can be comma-separated for bulk)'),
                    'changes_json' => $schema->string()->description('JSON string of attribute_code => new_value pairs to update (e.g. {"name": "New Name", "price": 29.99, "color": "Red"}). A per-locale attribute may also take a locale map, e.g. {"description": {"en_US": "...", "de_DE": "..."}}'),
                    'locale'       => $schema->string()->description('Locale code to write locale-scoped attributes to (e.g. de_DE). Defaults to the conversation locale. Attributes that are not per-locale ignore this.'),
                ];
            }

            /**
             * Apply the requested attribute changes to each SKU and report the outcome.
             */
            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.edit')) {
                    return $denied;
                }

                $sku = $request->string('sku')->toString();
                $changes_json = $request->string('changes_json')->toString();

                $changes = json_decode($changes_json, true) ?? [];

                if (empty($changes)) {
                    return json_encode(['error' => 'Invalid or empty changes JSON.']);
                }
                $skus = array_map(trim(...), explode(',', $sku));

                if ($denied = $this->denyMultiProductUnlessAllowed($this->context, $skus, 'catalog.products.bulk_edit')) {
                    return $denied;
                }

                $updated = 0;
                $errors = [];

                $productRepo = resolve(ProductRepository::class);
                $currencies = $this->writerService->getActiveCurrencyCodes();
                $activeLocaleCodes = array_flip(core()->getAllActiveLocales()->pluck('code')->all());

                $requestedLocale = trim($request->string('locale')->toString());
                $targetLocale = $requestedLocale !== '' ? $requestedLocale : (string) $this->context->locale;

                if (! isset($activeLocaleCodes[$targetLocale]) && $this->writesToTargetLocale($changes, $activeLocaleCodes)) {
                    return json_encode([
                        'error'          => "Unknown or inactive locale: {$targetLocale}",
                        'active_locales' => array_keys($activeLocaleCodes),
                    ]);
                }

                foreach ($skus as $s) {
                    $product = $productRepo->findOneByField('sku', $s);

                    if (! $product) {
                        $errors[] = "SKU not found: {$s}";

                        continue;
                    }

                    $productId = data_get($product, 'id');
                    $familyId = data_get($product, 'attribute_family_id');
                    $familyAttributes = $this->writerService->getFamilyAttributesPublic($familyId);

                    $values = $product->values ?? [];
                    $translatableFields = [];
                    $allChannels = core()->getAllChannels();

                    foreach ($changes as $code => $value) {
                        if ($code === 'status') {
                            $product->status = \in_array(strtolower((string) $value), ['1', 'active', 'yes', 'on', 'enabled'], true) ? 1 : 0;
                            $product->save();

                            continue;
                        }

                        // LLM sometimes passes locale-keyed objects like {"ar_AE": "text"}.
                        // Detect and route each locale value to the correct bucket.
                        if (is_array($value) && $value !== []) {
                            $localeKeys = array_keys($value);
                            $unknownLocaleKeys = array_values(array_filter(
                                $localeKeys,
                                fn (int|string $localeKey): bool => ! isset($activeLocaleCodes[(string) $localeKey])
                            ));
                            $looksLikeLocaleMap = $unknownLocaleKeys === [];

                            if (! $looksLikeLocaleMap && \count($unknownLocaleKeys) < \count($localeKeys)) {
                                $errors[] = "{$code}: unknown or inactive locale keys ".implode(', ', $unknownLocaleKeys)
                                    ." on SKU {$s}; active locales: ".implode(', ', array_keys($activeLocaleCodes));

                                continue;
                            }

                            if ($looksLikeLocaleMap && isset($familyAttributes[$code])) {
                                $meta = $familyAttributes[$code];

                                foreach ($value as $localeCode => $localeValue) {
                                    if (! isset($activeLocaleCodes[(string) $localeCode])) {
                                        continue;
                                    }
                                    if (! is_string($localeValue)) {
                                        continue;
                                    }
                                    if ($localeValue === '') {
                                        continue;
                                    }
                                    if ($localeValue === '0') {
                                        continue;
                                    }
                                    if ($meta['value_per_channel'] && $meta['value_per_locale']) {
                                        foreach ($allChannels as $ch) {
                                            $values['channel_locale_specific'][$ch->code][$localeCode][$code] = $localeValue;
                                        }
                                    } elseif ($meta['value_per_locale']) {
                                        $values['locale_specific'][$localeCode][$code] = $localeValue;
                                    }
                                }

                                continue;
                            }
                        }

                        // Guard: ensure text/textarea values are strings, not arrays
                        if (is_array($value) && isset($familyAttributes[$code])) {
                            $attrType = $familyAttributes[$code]['type'] ?? '';
                            if (\in_array($attrType, ['text', 'textarea'], true)) {
                                $errors[] = "{$code}: expected string but got array on SKU {$s}";

                                continue;
                            }
                        }

                        if (! isset($familyAttributes[$code])) {
                            if (is_string($value) || is_numeric($value) || is_bool($value)) {
                                $values['common'][$code] = $value;
                            }

                            continue;
                        }

                        $meta = $familyAttributes[$code];

                        // Handle price type
                        if ($meta['type'] === 'price' && is_numeric($value)) {
                            $priceObj = [];
                            foreach ($currencies as $curr) {
                                $priceObj[$curr] = (string) round((float) $value, 2);
                            }
                            $value = $priceObj;
                        }

                        // Handle select type
                        if (\in_array($meta['type'], ['select', 'multiselect'], true) && is_string($value)) {
                            $resolved = $this->writerService->resolveSelectValuePublic($code, $value, $meta['attribute_id']);
                            if ($resolved === null) {
                                $errors[] = "No matching option for {$code}='{$value}' on SKU {$s}";

                                continue;
                            }
                            $value = $resolved;
                        }

                        // Route to correct bucket — source locale + translate others
                        if ($meta['value_per_channel'] && $meta['value_per_locale']) {
                            foreach ($allChannels as $ch) {
                                $values['channel_locale_specific'][$ch->code][$targetLocale][$code] = $value;
                            }
                            if ($requestedLocale === '' && \in_array($meta['type'], ['text', 'textarea'], true) && is_string($value)) {
                                $translatableFields[$code] = $value;
                            }
                        } elseif ($meta['value_per_channel']) {
                            foreach ($allChannels as $ch) {
                                $values['channel_specific'][$ch->code][$code] = $value;
                            }
                        } elseif ($meta['value_per_locale']) {
                            $values['locale_specific'][$targetLocale][$code] = $value;
                            if ($requestedLocale === '' && \in_array($meta['type'], ['text', 'textarea'], true) && is_string($value)) {
                                $translatableFields[$code] = $value;
                            }
                        } else {
                            $values['common'][$code] = $value;
                        }
                    }

                    $productRepo->updateWithValues(['values' => $values], $productId);
                    $updated++;

                    // Dispatch translation for text fields
                    if ($translatableFields !== []) {
                        dispatch(new TranslateProductValuesJob(productId: $productId, sourceLocale: $targetLocale, fieldsToTranslate: $translatableFields, channel: $this->context->channel))->delay(now()->addSeconds(3));
                    }
                }

                return json_encode([
                    'result' => [
                        'updated' => $updated,
                        'skus'    => implode(', ', $skus),
                        'errors'  => $errors === [] ? null : $errors,
                    ],
                ]);
            }

            /**
             * Determine whether any change would be written to the target locale.
             *
             * Only a per-locale attribute given a plain value lands in the target locale;
             * a locale map names its own locales and every other attribute ignores the
             * locale, so an inactive page locale must not block a price or status update.
             *
             * @param  array<string, mixed>  $changes
             * @param  array<string, int>  $activeLocaleCodes
             */
            protected function writesToTargetLocale(array $changes, array $activeLocaleCodes): bool
            {
                $codes = array_keys(array_filter(
                    $changes,
                    fn (mixed $value, int|string $code): bool => $code !== 'status' && ! (
                        is_array($value)
                        && $value !== []
                        && array_all(array_keys($value), fn (int|string $localeKey): bool => isset($activeLocaleCodes[(string) $localeKey]))
                    ),
                    ARRAY_FILTER_USE_BOTH
                ));

                if ($codes === []) {
                    return false;
                }

                return resolve(AttributeRepository::class)
                    ->findWhereIn('code', $codes, ['value_per_locale'])
                    ->contains(fn (Attribute $attribute): bool => $attribute->isLocaleBasedAttribute());
            }
        };
    }
}

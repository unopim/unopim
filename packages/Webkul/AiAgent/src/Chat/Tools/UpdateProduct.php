<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Jobs\TranslateProductValuesJob;
use Webkul\AiAgent\Services\ProductWriterService;

class UpdateProduct implements PimTool
{
    use ChecksPermission;

    public function __construct(
        protected ProductWriterService $writerService,
    ) {}

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('update_product')
            ->for('Update product attributes by SKU. Pass changes as JSON in changes_json.')
            ->withStringParameter('sku', 'Product SKU to update (can be comma-separated for bulk)')
            ->withStringParameter('changes_json', 'JSON string of attribute_code => new_value pairs to update (e.g. {"name": "New Name", "price": 29.99, "color": "Red"})')
            ->using(function (string $sku, string $changes_json) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products.edit')) {
                    return $denied;
                }

                $changes = json_decode($changes_json, true) ?? [];

                if (empty($changes)) {
                    return json_encode(['error' => 'Invalid or empty changes JSON.']);
                }
                $skus = array_map('trim', explode(',', $sku));

                $updated = 0;
                $errors = [];

                $productRepo = app('Webkul\Product\Repositories\ProductRepository');
                $currencies = DB::table('currencies')->where('status', 1)->pluck('code')->toArray() ?: ['USD'];

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
                        if (is_array($value) && ! empty($value)) {
                            $localeKeys = array_keys($value);
                            $looksLikeLocaleMap = preg_match('/^[a-z]{2}_[A-Z]{2}$/', $localeKeys[0] ?? '');

                            if ($looksLikeLocaleMap && isset($familyAttributes[$code])) {
                                $meta = $familyAttributes[$code];

                                foreach ($value as $localeCode => $localeValue) {
                                    if (! is_string($localeValue) || empty($localeValue)) {
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
                                $values['channel_locale_specific'][$ch->code][$context->locale][$code] = $value;
                            }
                            if (\in_array($meta['type'], ['text', 'textarea'], true) && is_string($value)) {
                                $translatableFields[$code] = $value;
                            }
                        } elseif ($meta['value_per_channel']) {
                            foreach ($allChannels as $ch) {
                                $values['channel_specific'][$ch->code][$code] = $value;
                            }
                        } elseif ($meta['value_per_locale']) {
                            $values['locale_specific'][$context->locale][$code] = $value;
                            if (\in_array($meta['type'], ['text', 'textarea'], true) && is_string($value)) {
                                $translatableFields[$code] = $value;
                            }
                        } else {
                            $values['common'][$code] = $value;
                        }
                    }

                    $productRepo->updateWithValues(['values' => $values], $productId);
                    $updated++;

                    // Dispatch translation for text fields
                    if (! empty($translatableFields)) {
                        TranslateProductValuesJob::dispatch(
                            productId: $productId,
                            sourceLocale: $context->locale,
                            fieldsToTranslate: $translatableFields,
                            channel: $context->channel,
                        )->delay(now()->addSeconds(3));
                    }
                }

                return json_encode([
                    'result' => [
                        'updated' => $updated,
                        'skus'    => implode(', ', $skus),
                        'errors'  => empty($errors) ? null : $errors,
                    ],
                ]);
            });
    }
}

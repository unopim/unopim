<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

/**
 * Translates product attribute values to all target locales using AI.
 *
 * Dispatched after product creation/update to fill in translations
 * for locale-dependent fields (name, description, meta_title, etc.).
 * Runs asynchronously so the user doesn't wait for translations.
 */
class TranslateProductValuesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 180;

    /**
     * @param  int  $productId  Product to translate
     * @param  string  $sourceLocale  Locale of the original content (e.g. en_US)
     * @param  array<string, string>  $fieldsToTranslate  field_code => original_value pairs
     * @param  string  $channel  Source channel code
     */
    public function __construct(
        protected int $productId,
        protected string $sourceLocale,
        protected array $fieldsToTranslate,
        protected string $channel = 'default',
    ) {
        $this->queue = 'default';
    }

    public function handle(MagicAIPlatformRepository $platformRepository): void
    {
        $product = DB::table('products')->where('id', $this->productId)->first();

        if (! $product) {
            return;
        }

        $values = json_decode($product->values, true) ?? [];

        // Get all channels and their locales
        $allChannels = core()->getAllChannels();

        // Collect target locales (all active locales except the source)
        $targetLocales = [];
        foreach ($allChannels as $channel) {
            foreach ($channel->locales as $locale) {
                if ($locale->code !== $this->sourceLocale && ! \in_array($locale->code, $targetLocales, true)) {
                    $targetLocales[] = $locale->code;
                }
            }
        }

        if (empty($targetLocales)) {
            return; // Only one locale configured — nothing to translate
        }

        // Resolve AI platform
        $platform = $platformRepository->getDefault() ?? $platformRepository->getActiveList()->first();

        if (! $platform) {
            Log::warning("TranslateProductValuesJob: No AI platform for product {$product->sku}");

            return;
        }

        // Get locale display names for better translations
        $localeNames = DB::table('locales')
            ->whereIn('code', $targetLocales)
            ->pluck('name', 'code')
            ->toArray();

        // Load family attributes to know which bucket each field goes in
        $familyAttributes = [];
        if ($product->attribute_family_id) {
            $attrs = DB::table('attributes as a')
                ->join('attribute_group_mappings as agm', 'agm.attribute_id', '=', 'a.id')
                ->join('attribute_family_group_mappings as afgm', 'afgm.id', '=', 'agm.attribute_family_group_id')
                ->where('afgm.attribute_family_id', $product->attribute_family_id)
                ->select('a.code', 'a.value_per_channel', 'a.value_per_locale')
                ->get();

            foreach ($attrs as $attr) {
                $familyAttributes[$attr->code] = [
                    'value_per_channel' => (bool) $attr->value_per_channel,
                    'value_per_locale'  => (bool) $attr->value_per_locale,
                ];
            }
        }

        // Translate each field to each target locale
        foreach ($targetLocales as $targetLocale) {
            $localeName = $localeNames[$targetLocale] ?? $targetLocale;

            // Batch all fields into a single prompt for efficiency
            $fieldsText = '';
            $translatableFields = [];

            foreach ($this->fieldsToTranslate as $fieldCode => $originalValue) {
                if (empty($originalValue) || ! is_string($originalValue)) {
                    continue;
                }

                // Skip non-translatable fields
                if (\in_array($fieldCode, ['sku', 'url_key', 'product_number', 'image'], true)) {
                    continue;
                }

                $translatableFields[$fieldCode] = $originalValue;
                $fieldsText .= "{$fieldCode}: {$originalValue}\n";
            }

            if (empty($translatableFields)) {
                continue;
            }

            try {
                $prompt = "Translate these product fields from {$this->sourceLocale} to {$targetLocale} ({$localeName}). "
                    .'Return ONLY a JSON object with field_code as key and translated value as value. '
                    ."Keep the same field codes. Do not add explanations.\n\n{$fieldsText}";

                $response = MagicAI::usePlatform($platform)
                    ->setTemperature(0.3)
                    ->setMaxTokens(2000)
                    ->setPrompt($prompt, 'text')
                    ->ask();

                // Parse JSON from response
                $translated = $this->extractJson($response);

                if (empty($translated)) {
                    Log::warning("TranslateProductValuesJob: Failed to parse translation for {$product->sku} → {$targetLocale}");

                    continue;
                }

                // Apply translations to the correct buckets
                foreach ($translated as $fieldCode => $translatedValue) {
                    if (! isset($translatableFields[$fieldCode]) || empty($translatedValue)) {
                        continue;
                    }

                    $meta = $familyAttributes[$fieldCode] ?? ['value_per_channel' => true, 'value_per_locale' => true];

                    if ($meta['value_per_channel'] && $meta['value_per_locale']) {
                        foreach ($allChannels as $ch) {
                            if ($ch->locales->contains('code', $targetLocale)) {
                                $values['channel_locale_specific'][$ch->code][$targetLocale][$fieldCode] = $translatedValue;
                            }
                        }
                    } elseif ($meta['value_per_locale']) {
                        $values['locale_specific'][$targetLocale][$fieldCode] = $translatedValue;
                    }
                    // channel_specific and common fields don't vary by locale — skip
                }
            } catch (\Throwable $e) {
                Log::error("TranslateProductValuesJob: Translation to {$targetLocale} failed for {$product->sku}: {$e->getMessage()}");
            }
        }

        // Save all translations at once
        DB::table('products')
            ->where('id', $this->productId)
            ->update(['values' => json_encode($values)]);

        Log::info("TranslateProductValuesJob: Translated {$product->sku} to ".implode(', ', $targetLocales));
    }

    /**
     * Extract JSON from LLM response (handles markdown code blocks).
     */
    protected function extractJson(string $response): ?array
    {
        // Try direct JSON parse
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try extracting from ```json ... ``` blocks
        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $response, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Try extracting from { ... } anywhere in the response
        if (preg_match('/\{[^{}]*\}/', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}

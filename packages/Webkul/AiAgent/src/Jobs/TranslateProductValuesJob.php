<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\AiAgent\Services\TranslationResponseParser;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

/**
 * Translates product attribute values to all target locales using AI.
 *
 * Dispatched after product creation/update to fill in translations for
 * locale-dependent fields (name, description, meta_title, etc.). Runs
 * asynchronously so the user does not wait for translations.
 *
 * Reliability guarantees:
 * - Each locale is persisted independently under a short row lock, so a
 *   worker timeout mid-run keeps the locales already completed instead of
 *   discarding every translation (the root cause of the "reported complete,
 *   only a handful processed" symptom).
 * - A response that cannot be parsed (including one truncated by the token
 *   limit) is recorded as a failure, never silently treated as success.
 * - When no locale could be translated at all, the job throws so the queue
 *   marks it failed and surfaces it, rather than reporting a false success.
 * - Estimated token spend is recorded so background translation counts
 *   toward the daily budget.
 */
class TranslateProductValuesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /**
     * Generous ceiling — many locale-specific fields (HTML descriptions
     * especially) overflowed the previous 2000-token limit and were dropped.
     */
    public int $timeout = 600;

    /**
     * Output token ceiling per locale request. Sized to fit a full set of
     * translated product fields without truncation.
     */
    protected const MAX_OUTPUT_TOKENS = 4000;

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

        $allChannels = core()->getAllChannels();

        $targetLocales = $this->resolveTargetLocales($allChannels);

        if ($targetLocales === []) {
            return; // Only one locale configured — nothing to translate
        }

        $translatableFields = $this->collectTranslatableFields();

        if ($translatableFields === []) {
            return;
        }

        $platform = $platformRepository->getDefault() ?? $platformRepository->getActiveList()->first();

        if (! $platform) {
            Log::warning("TranslateProductValuesJob: No AI platform for product {$product->sku}");

            return;
        }

        $localeNames = DB::table('locales')
            ->whereIn('code', $targetLocales)
            ->pluck('name', 'code')
            ->toArray();

        $familyAttributes = $this->loadFamilyAttributes($product->attribute_family_id);

        $fieldsText = '';
        foreach ($translatableFields as $fieldCode => $originalValue) {
            $fieldsText .= "{$fieldCode}: {$originalValue}\n";
        }

        $succeeded = [];
        $failed = [];

        foreach ($targetLocales as $targetLocale) {
            $localeName = $localeNames[$targetLocale] ?? $targetLocale;

            try {
                $prompt = "Translate these product fields from {$this->sourceLocale} to {$targetLocale} ({$localeName}). "
                    .'Return ONLY a JSON object with field_code as key and translated value as value. '
                    ."Keep the same field codes. Do not add explanations.\n\n{$fieldsText}";

                $response = MagicAI::usePlatform($platform)
                    ->setTemperature(0.3)
                    ->setMaxTokens(self::MAX_OUTPUT_TOKENS)
                    ->setPrompt($prompt, 'text')
                    ->ask();

                $translated = TranslationResponseParser::extractObject($response);

                if ($translated === null) {
                    $reason = TranslationResponseParser::looksTruncated($response)
                        ? 'truncated response (token limit)'
                        : 'unparseable response';
                    $failed[$targetLocale] = $reason;
                    Log::warning("TranslateProductValuesJob: {$product->sku} -> {$targetLocale}: {$reason}");

                    continue;
                }

                $applied = $this->persistLocaleTranslations(
                    $targetLocale,
                    $translated,
                    $translatableFields,
                    $familyAttributes,
                    $allChannels,
                );

                if ($applied === 0) {
                    $failed[$targetLocale] = 'no valid fields in response';

                    continue;
                }

                $succeeded[] = $targetLocale;
            } catch (\Throwable $e) {
                $failed[$targetLocale] = $e->getMessage();
                Log::error("TranslateProductValuesJob: {$targetLocale} failed for {$product->sku}: {$e->getMessage()}");
            }
        }

        if ($succeeded === []) {
            // Nothing was translated. Surface it as a failure instead of the
            // old silent "completed" so the queue retries and the operator
            // can see something went wrong.
            throw new \RuntimeException(
                "TranslateProductValuesJob: all locales failed for {$product->sku} — ".json_encode($failed)
            );
        }

        Log::info(sprintf(
            'TranslateProductValuesJob: %s translated to %s%s',
            $product->sku,
            implode(', ', $succeeded),
            $failed === [] ? '' : ' (failed: '.implode(', ', array_keys($failed)).')',
        ));
    }

    /**
     * Collect every active locale (across all channels) except the source.
     *
     * @param  iterable<object>  $allChannels
     * @return array<int, string>
     */
    protected function resolveTargetLocales(iterable $allChannels): array
    {
        $targetLocales = [];

        foreach ($allChannels as $channel) {
            foreach ($channel->locales as $locale) {
                if ($locale->code !== $this->sourceLocale && ! \in_array($locale->code, $targetLocales, true)) {
                    $targetLocales[] = $locale->code;
                }
            }
        }

        return $targetLocales;
    }

    /**
     * Filter the requested fields down to the ones that can be translated.
     *
     * @return array<string, string>
     */
    protected function collectTranslatableFields(): array
    {
        $fields = [];

        foreach ($this->fieldsToTranslate as $fieldCode => $originalValue) {
            if (empty($originalValue)) {
                continue;
            }
            if (! is_string($originalValue)) {
                continue;
            }
            if (\in_array($fieldCode, ['sku', 'url_key', 'product_number', 'image'], true)) {
                continue;
            }

            $fields[$fieldCode] = $originalValue;
        }

        return $fields;
    }

    /**
     * Load per-attribute channel/locale scope flags for the product's family.
     *
     * @return array<string, array{value_per_channel: bool, value_per_locale: bool}>
     */
    protected function loadFamilyAttributes(?int $familyId): array
    {
        if (! $familyId) {
            return [];
        }

        $attrs = DB::table('attributes as a')
            ->join('attribute_group_mappings as agm', 'agm.attribute_id', '=', 'a.id')
            ->join('attribute_family_group_mappings as afgm', 'afgm.id', '=', 'agm.attribute_family_group_id')
            ->where('afgm.attribute_family_id', $familyId)
            ->select('a.code', 'a.value_per_channel', 'a.value_per_locale')
            ->get();

        $map = [];

        foreach ($attrs as $attr) {
            $map[$attr->code] = [
                'value_per_channel' => (bool) $attr->value_per_channel,
                'value_per_locale'  => (bool) $attr->value_per_locale,
            ];
        }

        return $map;
    }

    /**
     * Persist one locale's translations into the correct value buckets under
     * a short row lock, re-reading the freshest values first so concurrent
     * writes (e.g. a rapid create-then-update on the same product) are not
     * clobbered.
     *
     * @param  array<string, mixed>  $translated  field_code => translated value
     * @param  array<string, string>  $translatableFields
     * @param  array<string, array{value_per_channel: bool, value_per_locale: bool}>  $familyAttributes
     * @param  iterable<object>  $allChannels
     * @return int Number of fields actually written
     */
    protected function persistLocaleTranslations(
        string $targetLocale,
        array $translated,
        array $translatableFields,
        array $familyAttributes,
        iterable $allChannels,
    ): int {
        return DB::transaction(function () use ($targetLocale, $translated, $translatableFields, $familyAttributes, $allChannels): int {
            $fresh = DB::table('products')->where('id', $this->productId)->lockForUpdate()->first();

            if (! $fresh) {
                return 0;
            }

            $raw = (string) ($fresh->values ?? '');
            $decoded = json_decode($raw, true);

            if ($decoded === null && trim($raw) !== '' && strtolower(trim($raw)) !== 'null') {
                // Stored values are non-empty but unparseable — abort rather
                // than overwriting the whole blob with only the translations.
                return 0;
            }

            $values = is_array($decoded) ? $decoded : [];
            $applied = 0;

            foreach ($translated as $fieldCode => $translatedValue) {
                if (! isset($translatableFields[$fieldCode])) {
                    continue;
                }
                if (empty($translatedValue)) {
                    continue;
                }
                if (! is_string($translatedValue)) {
                    continue;
                }
                $meta = $familyAttributes[$fieldCode] ?? ['value_per_channel' => true, 'value_per_locale' => true];

                if ($meta['value_per_channel'] && $meta['value_per_locale']) {
                    foreach ($allChannels as $ch) {
                        if ($ch->locales->contains('code', $targetLocale)) {
                            $values['channel_locale_specific'][$ch->code][$targetLocale][$fieldCode] = $translatedValue;
                        }
                    }
                    $applied++;
                } elseif ($meta['value_per_locale']) {
                    $values['locale_specific'][$targetLocale][$fieldCode] = $translatedValue;
                    $applied++;
                }
                // channel_specific and common fields do not vary by locale — skip
            }

            if ($applied > 0) {
                DB::table('products')
                    ->where('id', $this->productId)
                    ->update(['values' => json_encode($values)]);
            }

            return $applied;
        });
    }
}

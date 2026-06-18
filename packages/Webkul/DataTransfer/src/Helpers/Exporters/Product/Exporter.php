<?php

namespace Webkul\DataTransfer\Helpers\Exporters\Product;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\DataTransfer\Contracts\JobTrackBatch as JobTrackBatchContract;
use Webkul\DataTransfer\Enums\ProductExportScope;
use Webkul\DataTransfer\Enums\ProductFilter;
use Webkul\DataTransfer\Enums\TimeCondition;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Formatters\ScopeFilterValue;
use Webkul\DataTransfer\Helpers\Sources\Export\ProductSource;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\Product\Repositories\ProductRepository;

class Exporter extends AbstractExporter
{
    /**
     * Static cache for channels/locales/currencies/attributes.
     * Shared across all ExportBatch jobs within the same worker process,
     * avoiding redundant DB queries on every batch.
     */
    protected static ?array $staticInitCache = null;

    /**
     * @var array
     */
    protected $channelsAndLocales = [];

    /**
     * @var array
     */
    protected $currencies = [];

    /**
     * Currency codes keyed by channel code.
     *
     * @var array
     */
    protected $channelCurrencies = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Lightweight per-attribute metadata (code + type, with a model reference kept for the rare
     * label/date formatting paths). Precomputed once per worker so the innermost export loop in
     * setAttributesValues() reads plain array keys instead of triggering Astrotomic Translatable's
     * getAttribute(), which calls config() on every property read (~110µs each, tens of millions of
     * times on a large catalog — the original cause of multi-minute, effectively hung exports).
     *
     * @var array<int, array{code: string, type: string, attribute: mixed}>
     */
    protected array $attributeMeta = [];

    /**
     * Attribute codes selected in the export profile. When empty every
     * attribute value is exported.
     *
     * @var array
     */
    protected $selectedAttributeCodes = [];

    /**
     * Memoized `optionCode => [locale => label]` maps keyed by attribute code, used when the
     * "use labels" output option is enabled.
     *
     * @var array
     */
    protected $optionLabelMaps = [];

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(
        protected JobTrackBatchRepository $exportBatchRepository,
        protected FileExportFileBuffer $exportFileBuffer,
        protected ChannelRepository $channelRepository,
        protected AttributeRepository $attributeRepository,
        protected ProductSource $productSource,
    ) {
        parent::__construct($exportBatchRepository, $exportFileBuffer);
    }

    /**
     * Initializes the channels and locales for the export process.
     * Uses a static in-process cache so that the DB queries run only once
     * per worker process regardless of how many ExportBatch jobs are handled.
     *
     * @return void
     */
    public function initilize()
    {
        if (self::$staticInitCache === null) {
            $channels = $this->channelRepository->with(['locales', 'currencies'])->all();
            $channelsAndLocales = [];
            $channelCurrencies = [];
            $currencies = [];

            foreach ($channels as $channel) {
                $channelCurrencyCodes = $channel->currencies->pluck('code')->toArray();

                $currencies = array_unique(array_merge($currencies, $channelCurrencyCodes));
                $channelsAndLocales[$channel->code] = $channel->locales->pluck('code')->toArray();
                $channelCurrencies[$channel->code] = $channelCurrencyCodes;
            }

            $attributes = $this->attributeRepository->all();

            self::$staticInitCache = [
                'channelsAndLocales' => $channelsAndLocales,
                'channelCurrencies'  => $channelCurrencies,
                'currencies'         => array_values($currencies),
                'attributes'         => $attributes,
                'attributeMeta'      => $this->buildAttributeMeta($attributes),
            ];
        }

        $this->channelsAndLocales = self::$staticInitCache['channelsAndLocales'];
        $this->channelCurrencies = self::$staticInitCache['channelCurrencies'];
        $this->currencies = self::$staticInitCache['currencies'];
        $this->attributes = self::$staticInitCache['attributes'];
        $this->attributeMeta = self::$staticInitCache['attributeMeta'];

        $this->applyScopeFilters();
    }

    /**
     * Flattens the attribute models into plain arrays so the export's innermost loop reads `code`
     * and `type` as array keys rather than Eloquent magic properties. This pays the Translatable
     * read cost exactly once per attribute (per worker) instead of once per attribute per product
     * per channel per locale.
     */
    protected function buildAttributeMeta($attributes): array
    {
        $meta = [];

        foreach ($attributes as $attribute) {
            $meta[] = [
                'code'      => $attribute->code,
                'type'      => $attribute->type,
                'attribute' => $attribute,
            ];
        }

        return $meta;
    }

    /**
     * Restricts the export scope to the channels and currencies selected in the
     * export profile filters. When a filter is empty the full scope is kept, so
     * existing profiles continue to export every channel and currency.
     */
    protected function applyScopeFilters(): void
    {
        $filters = $this->getFilters();

        $this->applyChannelScope(ScopeFilterValue::toCodes($filters[ProductExportScope::CHANNELS->value] ?? null));
        $this->applyLocaleScope(ScopeFilterValue::toCodes($filters[ProductExportScope::LOCALES->value] ?? null));
        $this->applyCurrencyScope(ScopeFilterValue::toCodes($filters[ProductExportScope::CURRENCIES->value] ?? null));
        $this->applyAttributeScope(ScopeFilterValue::toCodes($filters[ProductExportScope::ATTRIBUTES->value] ?? null));
    }

    /**
     * Restricts the export scope to the selected channels and their currencies.
     */
    protected function applyChannelScope(array $selectedChannels): void
    {
        if (empty($selectedChannels)) {
            return;
        }

        $this->channelsAndLocales = array_intersect_key(
            $this->channelsAndLocales,
            array_flip($selectedChannels)
        );

        $this->currencies = $this->currenciesForChannels($selectedChannels);
    }

    /**
     * Keeps only the selected locales within every channel's locale scope.
     */
    protected function applyLocaleScope(array $selectedLocales): void
    {
        if (empty($selectedLocales)) {
            return;
        }

        foreach ($this->channelsAndLocales as $channel => $locales) {
            $this->channelsAndLocales[$channel] = array_values(array_intersect($locales, $selectedLocales));
        }
    }

    /**
     * Keeps only the selected currencies within the current currency scope.
     */
    protected function applyCurrencyScope(array $selectedCurrencies): void
    {
        if (empty($selectedCurrencies)) {
            return;
        }

        $this->currencies = array_values(array_intersect($this->currencies, $selectedCurrencies));
    }

    /**
     * Records the selected attributes. Every attribute keeps its column in the
     * export file; only the selected ones carry values, the rest stay empty.
     */
    protected function applyAttributeScope(array $selectedAttributes): void
    {
        $this->selectedAttributeCodes = $selectedAttributes;
    }

    /**
     * Whether the attribute's values should be exported. True for every
     * attribute when no attribute selection is set on the profile.
     */
    protected function isAttributeValueExported(string $code): bool
    {
        return empty($this->selectedAttributeCodes)
            || in_array($code, $this->selectedAttributeCodes, true);
    }

    /**
     * Collects the distinct currencies belonging to the given channels.
     */
    protected function currenciesForChannels(array $channelCodes): array
    {
        $currencies = [];

        foreach ($channelCodes as $channelCode) {
            $currencies = array_merge($currencies, $this->channelCurrencies[$channelCode] ?? []);
        }

        return array_values(array_unique($currencies));
    }

    /**
     * Pre-flight feasibility guard. A product export emits one row per channel-locale pair and one
     * column per attribute, so the output is productCount × pairs × attributes — which explodes on a
     * wide catalog ("export everything" with no filters). Estimate that here and reject the export
     * up front (clear message) rather than letting it fill the disk and block the queue for hours.
     */
    protected function assertExportIsFeasible($results): void
    {
        $productCount = method_exists($results, 'count') ? (int) $results->count() : 0;

        if ($productCount <= 0) {
            return;
        }

        $rows = $productCount * max(1, $this->countChannelLocalePairs());
        $columns = max(1, $this->attributeRepository->count());

        $this->guardAgainstOversizedExport($rows, $columns);
    }

    /**
     * Counts the channel-locale pairs each product expands into, honouring the profile's channel and
     * locale filters. Reads only channels (a small set) — never the full attribute list — so it stays
     * cheap during batch creation.
     */
    protected function countChannelLocalePairs(): int
    {
        $filters = $this->getFilters();
        $channelCodes = ScopeFilterValue::toCodes($filters[ProductExportScope::CHANNELS->value] ?? null);
        $localeCodes = ScopeFilterValue::toCodes($filters[ProductExportScope::LOCALES->value] ?? null);

        $pairs = 0;

        foreach ($this->channelRepository->with(['locales'])->all() as $channel) {
            if (! empty($channelCodes) && ! in_array($channel->code, $channelCodes, true)) {
                continue;
            }

            foreach ($channel->locales as $locale) {
                if (! empty($localeCodes) && ! in_array($locale->code, $localeCodes, true)) {
                    continue;
                }

                $pairs++;
            }
        }

        return $pairs;
    }

    /**
     * Start the import process
     */
    public function exportBatch(JobTrackBatchContract $batch, $filePath): bool
    {
        Event::dispatch('data_transfer.exports.batch.export.before', $batch);

        $this->initilize();

        $this->prepareProducts($batch, $filePath);

        /**
         * Update export batch process state summary
         */
        $this->updateBatchState($batch->id, Export::STATE_PROCESSED);

        Event::dispatch('data_transfer.exports.batch.export.after', $batch);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults()
    {
        $filters = $this->getFilters();

        $filters[ProductFilter::UPDATED_AFTER->value] = $this->resolveUpdatedAfter($filters);
        $filters[ProductFilter::UPDATED_BEFORE->value] = $this->resolveUpdatedBefore($filters);

        $requestParam['filters'] = $filters;

        return $this->productSource->getResults($requestParam, $this->source, self::BATCH_SIZE);
    }

    /**
     * Resolves the time condition filter into the lower "updated since"
     * timestamp. Returns null when no date condition applies, so the export
     * proceeds without a lower time constraint.
     */
    protected function resolveUpdatedAfter(array $filters): ?string
    {
        $condition = $filters[ProductFilter::TIME_CONDITION->value] ?? null;

        $date = match ($condition) {
            TimeCondition::LAST_N_DAYS->value       => $this->daysAgo($filters[ProductFilter::TIME_VALUE->value] ?? null),
            TimeCondition::SINCE_LAST_EXPORT->value => $this->lastExportCompletedAt(),
            TimeCondition::BETWEEN_DATES->value     => $this->parseDate($filters[ProductFilter::TIME_DATE->value] ?? null)?->startOfDay(),
            default                                 => null,
        };

        return $date?->toDateTimeString();
    }

    /**
     * Resolves the upper "updated until" timestamp for the between-dates
     * condition. Returns null for every other condition so the export keeps an
     * open-ended upper bound.
     */
    protected function resolveUpdatedBefore(array $filters): ?string
    {
        $condition = $filters[ProductFilter::TIME_CONDITION->value] ?? null;

        $date = match ($condition) {
            TimeCondition::BETWEEN_DATES->value => $this->parseDate($filters[ProductFilter::TIME_DATE_END->value] ?? null)?->endOfDay(),
            default                             => null,
        };

        return $date?->toDateTimeString();
    }

    protected function daysAgo(mixed $days): ?Carbon
    {
        $days = (int) $days;

        return $days > 0 ? now()->subDays($days) : null;
    }

    protected function parseDate(mixed $date): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Completion timestamp of the most recent successful export run for this
     * profile, or null when none exists yet.
     */
    protected function lastExportCompletedAt(): ?Carbon
    {
        $jobInstanceId = $this->export->jobInstance?->id;

        if (! $jobInstanceId) {
            return null;
        }

        $completedAt = JobTrack::query()
            ->where('job_instances_id', $jobInstanceId)
            ->where('state', Export::STATE_COMPLETED)
            ->where('id', '!=', $this->export->id)
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->value('completed_at');

        return $completedAt ? Carbon::parse($completedAt) : null;
    }

    protected function getItemsFromIds(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        if (! $this->source) {
            $this->source = app(ProductRepository::class);
        }

        return $this->source
            ->with([
                'super_attributes:id,code',
                'parent:id,sku',
                'attribute_family:id,code',
            ])
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * Prepare products from the current batch and stream each expanded row to the export buffer.
     *
     * Rows are written one at a time as they are built rather than collected and returned, so peak
     * memory stays bounded to a single row regardless of BATCH_SIZE, attribute count, or the number
     * of channel-locale pairs.
     */
    public function prepareProducts(JobTrackBatchContract $batch, $filePath)
    {
        $flatIds = array_column($batch->data, 'id');

        $productsByIds = $this->getItemsFromIds($flatIds);

        foreach ($productsByIds as $product) {
            // Build rowData directly from model properties instead of calling toArray().
            // Calling $product->toArray() triggers attribute_family->toArray() which invokes
            // Astrotomic Translatable::getTranslation() for every configured locale (~8ms/product).
            // Direct property access avoids that entirely.
            $productValues = $product->values ?? [];

            $rowData = [
                'type'             => $product->type,
                'sku'              => $product->sku,
                'status'           => $product->status,
                'super_attributes' => $product->type === 'configurable'
                    ? $product->super_attributes->toArray()
                    : [],
                'attribute_family' => ['code' => $product->attribute_family?->code],
                'values'           => $productValues,
            ];

            $family = $product->attribute_family?->code;
            $parentSku = $product->type === 'simple'
                ? optional($product->parent)->sku
                : null;

            $sku = $product->sku;
            $type = $product->type;
            $status = $product->status ? 'true' : 'false';
            $configurableAttributes = $this->getSuperAttributes($rowData);
            $categories = $this->getCategories($rowData);
            $upSells = $this->getAssociations($rowData, 'up_sells');
            $crossSells = $this->getAssociations($rowData, 'cross_sells');
            $relatedProducts = $this->getAssociations($rowData, 'related_products');

            $commonFields = $this->getCommonFields($rowData);
            unset($commonFields['sku']);

            foreach ($this->channelsAndLocales as $channel => $locales) {
                foreach ($locales as $locale) {
                    $localeSpecificFields = $this->getLocaleSpecificFields($rowData, $locale);
                    $channelSpecificFields = $this->getChannelSpecificFields($rowData, $channel);
                    $channelLocaleSpecificFields = $this->getChannelLocaleSpecificFields($rowData, $channel, $locale);

                    $mergedFields = array_merge(
                        $commonFields,
                        $localeSpecificFields,
                        $channelSpecificFields,
                        $channelLocaleSpecificFields
                    );

                    $values = $this->setAttributesValues($mergedFields, $filePath, $locale);

                    $row = array_merge([
                        'channel'                 => $channel,
                        'locale'                  => $locale,
                        'sku'                     => $sku,
                        'status'                  => $status,
                        'type'                    => $type,
                        'parent'                  => $parentSku,
                        'attribute_family'        => $family,
                        'configurable_attributes' => $configurableAttributes,
                        'categories'              => $categories,
                        'up_sells'                => $upSells,
                        'cross_sells'             => $crossSells,
                        'related_products'        => $relatedProducts,
                    ], $values);

                    /**
                     * Stream the row immediately. The buffer keeps its "one line = array of rows"
                     * contract (consumed by flush()/FlatItemBuffer::addData()), so the row is wrapped
                     * in a single-element array. Writing here — rather than accumulating into a batch
                     * array and writing once — is what keeps a wide catalog from building a multi-GB
                     * structure and OOM-killing the export worker.
                     */
                    $this->exportBuffer->write([$row]);
                }
            }

            $this->createdItemsCount++;
        }
    }

    public function getSuperAttributes($data)
    {
        if (! isset($data['super_attributes'])) {
            return null;
        }

        $configurable_attributes = array_map(function ($data) {
            return $data['code'];
        }, $data['super_attributes'] ?? []);

        return implode(',', $configurable_attributes);
    }

    /**
     * Applies the export profile's output formatting (date format, readable labels) to a single
     * resolved attribute value. Shared by this exporter and any subclass (e.g. the DAM exporter)
     * that reimplements setAttributesValues, so the formatting rules live in exactly one place.
     */
    protected function applyOutputFormatting($attribute, mixed $value, ?string $locale = null): mixed
    {
        $filters = $this->getFilters();

        if (($filters['use_labels'] ?? '0') !== '0') {
            $value = $this->resolveValueLabels($attribute, $value, $locale);
        }

        $dateFormat = $filters['date_format'] ?? null;

        if (! empty($dateFormat)) {
            if ($attribute->type === 'date') {
                $value = $this->formatDateValue($value, $dateFormat);
            } elseif ($attribute->type === 'datetime') {
                $value = $this->formatDateValue($value, $dateFormat.' H:i:s');
            }
        }

        return $value;
    }

    /**
     * Replaces option codes with their readable labels for select/multiselect attributes, using the
     * label of the given (row) locale and falling back to the code when no label exists. Non-option
     * attributes and empty values are returned untouched.
     */
    protected function resolveValueLabels($attribute, mixed $value, ?string $locale): mixed
    {
        if (! in_array($attribute->type, ['select', 'multiselect'])) {
            return $value;
        }

        if ($value === null || $value === '') {
            return $value;
        }

        $map = $this->optionLabelMap($attribute);

        $resolve = fn ($code) => $map[(string) $code][$locale] ?? (string) $code;

        if (is_array($value)) {
            return array_map($resolve, $value);
        }

        return $resolve($value);
    }

    /**
     * Builds (and memoizes per attribute) an `optionCode => [locale => label]` lookup so option
     * labels are resolved without re-querying the option translations for every product row.
     */
    protected function optionLabelMap($attribute): array
    {
        $code = $attribute->code;

        if (isset($this->optionLabelMaps[$code])) {
            return $this->optionLabelMaps[$code];
        }

        $map = [];

        foreach ($attribute->options as $option) {
            foreach ($option->translations as $translation) {
                $map[(string) $option->code][$translation->locale] = $translation->label;
            }
        }

        return $this->optionLabelMaps[$code] = $map;
    }

    /**
     * Builds the `columnKey => attribute label` header map used when "use_labels" is enabled,
     * labelling attribute columns (and their per-currency price variants) in the first exported
     * locale. Structural columns (sku, channel, …) are intentionally left as codes. The exporter
     * is initialized on demand because the finalisation (flush) stage does not run initilize().
     */
    protected function getHeaderLabels(): array
    {
        if (($this->getFilters()['use_labels'] ?? '0') === '0') {
            return [];
        }

        if (empty($this->attributes)) {
            $this->initilize();
        }

        $locale = $this->headerLocale();
        $labels = [];

        foreach ($this->attributes as $attribute) {
            if (in_array($attribute->code, ['sku', 'status'])) {
                continue;
            }

            $label = $attribute->translate($locale)?->name ?: $attribute->code;

            if ($attribute->type === AttributeTypes::PRICE_ATTRIBUTE_TYPE) {
                foreach ($this->currencies as $currency) {
                    $labels["{$attribute->code} ({$currency})"] = "{$label} ({$currency})";
                }

                continue;
            }

            $labels[$attribute->code] = $label;
        }

        return $labels;
    }

    /**
     * The locale used for the (single) header row: the first locale of the exported scope.
     */
    protected function headerLocale(): ?string
    {
        foreach ($this->channelsAndLocales as $locales) {
            if (! empty($locales)) {
                return $locales[0];
            }
        }

        return null;
    }

    /**
     * Formats a date/datetime value with the given format, returning the original value unchanged
     * when it is empty or cannot be parsed (so malformed data is never silently dropped).
     */
    protected function formatDateValue(mixed $value, string $format): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Sets attribute values for a product. If an attribute is not present in the given values array,
     *
     * @return array
     */
    protected function setAttributesValues(array $values, mixed $filePath, ?string $locale = null)
    {
        $attributeValues = [];
        $filters = $this->getFilters();
        $withMedia = (bool) ($filters['with_media'] ?? false);

        /**
         * Output formatting (readable labels / custom date format) is opt-in and rarely enabled.
         * Resolving it once here lets the hot loop skip the per-cell applyOutputFormatting() call —
         * and the Eloquent model access it performs — whenever neither option is on.
         */
        $formatOutput = ($filters['use_labels'] ?? '0') !== '0'
            || ! empty($filters['date_format'] ?? null);

        foreach ($this->attributeMeta as $meta) {
            $code = $meta['code'];
            $type = $meta['type'];

            if ($code === 'sku' || $code === 'status') {
                continue;
            }

            $isPrice = $type === AttributeTypes::PRICE_ATTRIBUTE_TYPE;

            if (! $this->isAttributeValueExported($code)) {
                if ($isPrice) {
                    foreach ($this->currencies as $currency) {
                        $attributeValues["{$code} ({$currency})"] = null;
                    }

                    continue;
                }

                $attributeValues[$code] = null;

                continue;
            }

            $rawValue = $values[$code] ?? null;

            if (
                $withMedia
                && ($type === AttributeTypes::FILE_ATTRIBUTE_TYPE
                    || $type === AttributeTypes::IMAGE_ATTRIBUTE_TYPE
                    || $type === AttributeTypes::GALLERY_ATTRIBUTE_TYPE)
            ) {
                $mediaPaths = (array) $rawValue;
                foreach ($mediaPaths as $path) {
                    if (! empty($path)) {
                        $this->copyMedia($path, $filePath->getTemporaryPath().'/'.$path);
                    }
                }

                $attributeValues[$code] = implode(', ', array_filter($mediaPaths));

                continue;
            }

            if ($isPrice) {
                $priceData = is_array($rawValue) ? $rawValue : [];

                foreach ($this->currencies as $currency) {
                    $attributeValues["{$code} ({$currency})"] = $priceData[$currency] ?? null;
                }

                continue;
            }

            if ($formatOutput) {
                $rawValue = $this->applyOutputFormatting($meta['attribute'], $rawValue, $locale);
            }

            if (is_array($rawValue)) {
                $rawValue = implode(', ', $rawValue);
            }

            $attributeValues[$code] = EscapeFormulaOperators::escapeValue($rawValue);
        }

        return $attributeValues;
    }

    /**
     * Retrieves and formats the common fields for a product.
     *
     * @return array
     */
    protected function getCommonFields(array $data)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('common', $data['values'])
        ) {
            return [];
        }

        return $data['values']['common'];
    }

    /**
     * Retrieves and formats the locale-specific fields for a product.
     *
     * @return array
     */
    protected function getLocaleSpecificFields(array $data, string $locale)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('locale_specific', $data['values'])
        ) {
            return [];
        }

        return $data['values']['locale_specific'][$locale] ?? [];
    }

    /**
     * Retrieves and formats the channel-specific fields for a product.
     *
     * @return array
     */
    protected function getChannelSpecificFields(array $data, string $channel)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('channel_specific', $data['values'])
        ) {
            return [];
        }

        return $data['values']['channel_specific'][$channel] ?? [];
    }

    /**
     * Retrieves and formats the channel-locale-specific fields for a product.
     *
     * @return array
     */
    protected function getChannelLocaleSpecificFields(array $data, string $channel, string $locale)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('channel_locale_specific', $data['values'])
        ) {
            return [];
        }

        return $data['values']['channel_locale_specific'][$channel][$locale] ?? [];
    }

    /**
     * Retrieves and formats the categories associated with a product.
     *
     * @return string|null
     */
    protected function getCategories(array $data)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('categories', $data['values'])
            || ! is_array($data['values']['categories'])
        ) {
            return;
        }

        return implode(',', $data['values']['categories']);
    }

    /**
     * Retrieves and formats the associated products for a given data row and type.
     *
     * @return string|null
     */
    protected function getAssociations(array $data, string $type)
    {
        if (
            ! array_key_exists('values', $data)
            || ! array_key_exists('associations', $data['values'])
            || ! is_array($data['values']['associations'])
            || ! array_key_exists($type, $data['values']['associations'])
        ) {
            return;
        }

        return implode(',', $data['values']['associations'][$type]) ?? null;
    }
}

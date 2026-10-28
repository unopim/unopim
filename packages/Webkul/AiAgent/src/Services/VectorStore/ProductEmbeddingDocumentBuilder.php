<?php

namespace Webkul\AiAgent\Services\VectorStore;

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Services\ProductValueMapper;
use Webkul\Product\Type\AbstractType;

/**
 * Builds the embeddable text document (and its content hash) for products.
 *
 * Only descriptive attribute types are embedded, in a single locale of the
 * default channel, so volatile prices, file paths and dates never change the
 * content hash. Variants inherit their parent's text ahead of their own values.
 */
class ProductEmbeddingDocumentBuilder
{
    protected const TEXT_TYPES = [
        Attribute::TEXT_TYPE,
        Attribute::TEXTAREA_TYPE,
    ];

    protected const OPTION_TYPES = [
        Attribute::SELECT_FIELD_TYPE,
        Attribute::MULTISELECT_FIELD_TYPE,
        Attribute::CHECKBOX_FIELD_TYPE,
    ];

    public function __construct(
        protected ?AttributeRepository $attributeRepository = null,
        protected ?AttributeOptionRepository $attributeOptionRepository = null,
        protected ?ProductValueMapper $valueMapper = null,
    ) {}

    /**
     * Build the embedding payload for a single product row.
     *
     * @param  array<string, mixed>|string|null  $values  decoded (or raw JSON) product values
     * @param  array<string, mixed>|string|null  $parentValues  decoded (or raw JSON) values of the variant's parent
     * @return array{product_id: int, sku: ?string, text: string, content_hash: string}
     */
    public function build(int $productId, ?string $sku, array|string|null $values, array|string|null $parentValues = null): array
    {
        return $this->buildMany([[
            'id'            => $productId,
            'sku'           => $sku,
            'values'        => $values,
            'parent_values' => $parentValues,
        ]])[0];
    }

    /**
     * Build the embedding payloads for a batch of product rows with a constant number of queries.
     *
     * @param  array<int, array{id: int, sku: ?string, values: array<string, mixed>|string|null, parent_values?: array<string, mixed>|string|null}>  $rows
     * @return array<int, array{product_id: int, sku: ?string, text: string, content_hash: string}>
     */
    public function buildMany(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $products = array_map(fn (array $row): array => [
            'values'        => $this->decode($row['values']),
            'parent_values' => $this->decode($row['parent_values'] ?? null),
        ], array_values($rows));

        $attributes = $this->embeddableAttributes($products);
        $channel = (string) core()->getDefaultChannelCode();
        $locale = core()->getDefaultLocaleCodeFromDefaultChannel();

        $scoped = array_map(
            fn (array $product): array => $this->scopedFields($product, $attributes, $channel, $locale),
            $products,
        );

        $labels = $this->optionLabels($scoped, $attributes);

        $documents = [];

        foreach (array_values($rows) as $position => $row) {
            $documents[] = $this->document(
                (int) $row['id'],
                $row['sku'],
                $this->render($scoped[$position]['fields'], $attributes, $labels[$scoped[$position]['locale']] ?? []),
            );
        }

        return $documents;
    }

    /**
     * Attribute codes never embedded, regardless of their type.
     *
     * @return array<int, string>
     */
    protected function excludedAttributeCodes(): array
    {
        return array_map(strval(...), (array) config('ai-agent.vector_store.excluded_attributes', ['url_key']));
    }

    /**
     * Attribute types whose values are embedded.
     *
     * @return array<int, string>
     */
    protected function embeddableTypes(): array
    {
        return [
            ...self::TEXT_TYPES,
            ...self::OPTION_TYPES,
            Attribute::BOOLEAN_FIELD_TYPE,
            Attribute::MEASUREMENT_FIELD_TYPE,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(array|string|null $values): array
    {
        if (is_string($values)) {
            $values = json_decode($values, true);
        }

        return is_array($values) ? $values : [];
    }

    /**
     * Load the id and type of every embeddable attribute used by the batch, keyed by code.
     *
     * @param  array<int, array{values: array<string, mixed>, parent_values: array<string, mixed>}>  $products
     * @return array<string, array{id: int, type: string}>
     */
    protected function embeddableAttributes(array $products): array
    {
        $codes = [];

        foreach ($products as $product) {
            foreach ([$product['values'], $product['parent_values']] as $values) {
                foreach ($this->valueBuckets($values) as $bucket) {
                    $codes += array_fill_keys(array_map(strval(...), array_keys($bucket)), true);
                }
            }
        }

        $codes = array_values(array_diff(array_keys($codes), ['sku', ...$this->excludedAttributeCodes()]));

        if ($codes === []) {
            return [];
        }

        return ($this->attributeRepository ?? resolve(AttributeRepository::class))
            ->findWhereIn('code', $codes, ['id', 'code', 'type'])
            ->filter(fn ($attribute): bool => in_array($attribute->type, $this->embeddableTypes(), true))
            ->mapWithKeys(fn ($attribute): array => [$attribute->code => ['id' => (int) $attribute->id, 'type' => (string) $attribute->type]])
            ->all();
    }

    /**
     * Every attribute value bucket of a product values array.
     *
     * @param  array<string, mixed>  $values
     * @return array<int, array<string, mixed>>
     */
    protected function valueBuckets(array $values): array
    {
        $buckets = [$values[AbstractType::COMMON_VALUES_KEY] ?? []];

        foreach ([AbstractType::LOCALE_VALUES_KEY, AbstractType::CHANNEL_VALUES_KEY] as $key) {
            foreach ((array) ($values[$key] ?? []) as $bucket) {
                $buckets[] = $bucket;
            }
        }

        foreach ((array) ($values[AbstractType::CHANNEL_LOCALE_VALUES_KEY] ?? []) as $locales) {
            foreach ((array) $locales as $bucket) {
                $buckets[] = $bucket;
            }
        }

        return array_filter($buckets, is_array(...));
    }

    /**
     * Resolve the embeddable fields of a product (parent first for variants) in one channel and locale.
     *
     * The default channel's locale wins; another locale or channel is used only when it has no text.
     *
     * @param  array{values: array<string, mixed>, parent_values: array<string, mixed>}  $product
     * @param  array<string, array{id: int, type: string}>  $attributes
     * @return array{locale: string, fields: array<string, mixed>}
     */
    protected function scopedFields(array $product, array $attributes, string $channel, string $locale): array
    {
        $mapper = $this->valueMapper ?? resolve(ProductValueMapper::class);
        $fallback = null;

        foreach ($this->candidateLocales($product, $locale) as $candidateLocale) {
            foreach ($this->candidateChannels($product, $channel) as $candidateChannel) {
                $inherited = array_intersect_key($mapper->getScopedFields(['values' => $product['parent_values']], $candidateChannel, $candidateLocale), $attributes);
                $own = array_intersect_key($mapper->getScopedFields(['values' => $product['values']], $candidateChannel, $candidateLocale), $attributes);

                $fields = [
                    ...$this->ordered(array_merge($inherited, array_intersect_key($own, $inherited)), $attributes),
                    ...$this->ordered(array_diff_key($own, $inherited), $attributes),
                ];

                $scoped = ['locale' => $candidateLocale, 'fields' => $fields];
                $fallback ??= $scoped;

                if ($this->hasText($fields, $attributes)) {
                    return $scoped;
                }
            }
        }

        return $fallback ?? ['locale' => $locale, 'fields' => []];
    }

    /**
     * Order fields so the most descriptive text leads and survives the length cap, and SEO copy trails
     * so its duplicates of the name and descriptions are the ones dropped.
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, array{id: int, type: string}>  $attributes
     * @return array<string, mixed>
     */
    protected function ordered(array $fields, array $attributes): array
    {
        $rank = fn (string $code): int => match (true) {
            $code === 'name'                                                 => 0,
            str_starts_with($code, 'meta_')                                  => 6,
            $attributes[$code]['type'] === Attribute::TEXTAREA_TYPE          => 1,
            $attributes[$code]['type'] === Attribute::TEXT_TYPE              => 2,
            in_array($attributes[$code]['type'], self::OPTION_TYPES, true)   => 3,
            $attributes[$code]['type'] === Attribute::MEASUREMENT_FIELD_TYPE => 4,
            default                                                          => 5,
        };

        uksort($fields, fn (int|string $a, int|string $b): int => $rank((string) $a) <=> $rank((string) $b));

        return $fields;
    }

    /**
     * @param  array{values: array<string, mixed>, parent_values: array<string, mixed>}  $product
     * @return array<int, string>
     */
    protected function candidateLocales(array $product, string $locale): array
    {
        $locales = [];

        foreach ([$product['parent_values'], $product['values']] as $values) {
            $locales = [...$locales, ...array_keys((array) ($values[AbstractType::LOCALE_VALUES_KEY] ?? []))];

            foreach ((array) ($values[AbstractType::CHANNEL_LOCALE_VALUES_KEY] ?? []) as $channelLocales) {
                $locales = [...$locales, ...array_keys((array) $channelLocales)];
            }
        }

        $locales = array_map(strval(...), $locales);
        sort($locales);

        return array_values(array_unique([$locale, ...$locales]));
    }

    /**
     * @param  array{values: array<string, mixed>, parent_values: array<string, mixed>}  $product
     * @return array<int, string>
     */
    protected function candidateChannels(array $product, string $channel): array
    {
        $channels = [];

        foreach ([$product['parent_values'], $product['values']] as $values) {
            foreach ([AbstractType::CHANNEL_VALUES_KEY, AbstractType::CHANNEL_LOCALE_VALUES_KEY] as $key) {
                $channels = [...$channels, ...array_keys((array) ($values[$key] ?? []))];
            }
        }

        $channels = array_map(strval(...), $channels);
        sort($channels);

        return array_values(array_unique([$channel, ...$channels]));
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<string, array{id: int, type: string}>  $attributes
     */
    protected function hasText(array $fields, array $attributes): bool
    {
        return array_any($fields, fn ($value, $code): bool => in_array($attributes[$code]['type'], self::TEXT_TYPES, true) && $this->cleanText($value) !== '');
    }

    /**
     * Resolve option labels for every option value in the batch, keyed by locale, attribute code and option code.
     *
     * @param  array<int, array{locale: string, fields: array<string, mixed>}>  $scoped
     * @param  array<string, array{id: int, type: string}>  $attributes
     * @return array<string, array<string, array<string, string>>>
     */
    protected function optionLabels(array $scoped, array $attributes): array
    {
        $attributeIds = [];
        $optionCodes = [];
        $locales = [];

        foreach ($scoped as $product) {
            foreach ($product['fields'] as $code => $value) {
                if (! in_array($attributes[$code]['type'], self::OPTION_TYPES, true)) {
                    continue;
                }

                $attributeIds[$attributes[$code]['id']] = true;
                $locales[$product['locale']] = true;

                foreach ($this->optionCodes($value) as $optionCode) {
                    $optionCodes[$optionCode] = true;
                }
            }
        }

        if ($optionCodes === []) {
            return [];
        }

        $codesById = array_flip(array_map(fn (array $attribute): int => $attribute['id'], $attributes));
        $locales = array_map(strval(...), array_keys($locales));

        $options = ($this->attributeOptionRepository ?? resolve(AttributeOptionRepository::class))
            ->with(['translations' => fn ($query) => $query->whereIn('locale', $locales)])
            ->scopeQuery(fn ($query) => $query
                ->whereIn('attribute_id', array_keys($attributeIds))
                ->whereIn('code', array_map(strval(...), array_keys($optionCodes))))
            ->all(['id', 'attribute_id', 'code']);

        $labels = [];

        foreach ($options as $option) {
            foreach ($option->translations as $translation) {
                if ($translation->label === null || trim($translation->label) === '') {
                    continue;
                }

                $labels[$translation->locale][$codesById[$option->attribute_id]][$option->code] = trim($translation->label);
            }
        }

        return $labels;
    }

    /**
     * @return array<int, string>
     */
    protected function optionCodes(mixed $value): array
    {
        $codes = is_array($value) ? $value : explode(',', is_scalar($value) ? (string) $value : '');

        return array_values(array_filter(
            array_map(fn ($code): string => is_scalar($code) ? trim((string) $code) : '', $codes),
            fn (string $code): bool => $code !== '',
        ));
    }

    /**
     * Render the scoped fields as "code: value" lines, skipping values already present.
     *
     * @param  array<string, mixed>  $fields
     * @param  array<string, array{id: int, type: string}>  $attributes
     * @param  array<string, array<string, string>>  $labels
     * @return array<int, string>
     */
    protected function render(array $fields, array $attributes, array $labels): array
    {
        $lines = [];
        $seen = [];

        foreach ($fields as $code => $value) {
            $type = $attributes[$code]['type'];

            if ($type === Attribute::BOOLEAN_FIELD_TYPE) {
                if (filter_var($value, FILTER_VALIDATE_BOOL)) {
                    $lines[] = $code;
                }

                continue;
            }

            $text = match (true) {
                in_array($type, self::OPTION_TYPES, true) => implode(', ', array_map(
                    fn (string $optionCode): string => $labels[$code][$optionCode] ?? $optionCode,
                    $this->optionCodes($value),
                )),
                $type === Attribute::MEASUREMENT_FIELD_TYPE => $this->measurementText($value),
                default                                     => $this->cleanText($value),
            };

            $key = mb_strtolower($text);

            if ($text === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $lines[] = $code.': '.$text;
        }

        return $lines;
    }

    protected function measurementText(mixed $value): string
    {
        if (! is_array($value) || ! is_numeric($value['amount'] ?? null)) {
            return '';
        }

        $amount = (string) $value['amount'];

        if (str_contains($amount, '.')) {
            $amount = rtrim(rtrim($amount, '0'), '.');
        }

        $unit = $value['symbol'] ?? $value['unit'] ?? '';

        return trim($amount.' '.(is_scalar($unit) ? $unit : ''));
    }

    protected function cleanText(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        $text = html_entity_decode(strip_tags(str_replace('><', '> <', (string) $value)), ENT_QUOTES | ENT_HTML5);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @param  array<int, string>  $lines
     * @return array{product_id: int, sku: ?string, text: string, content_hash: string}
     */
    protected function document(int $productId, ?string $sku, array $lines): array
    {
        if ($sku !== null && trim($sku) !== '') {
            array_unshift($lines, 'sku: '.trim($sku));
        }

        $text = implode("\n", $lines);

        $maxLength = max(100, (int) config('ai-agent.vector_store.max_document_length', 6000));

        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }

        return [
            'product_id'   => $productId,
            'sku'          => $sku,
            'text'         => $text,
            'content_hash' => hash('sha256', $text),
        ];
    }
}

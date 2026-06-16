<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Enums\CompletenessCondition;
use Webkul\DataTransfer\Enums\ProductExportScope;
use Webkul\DataTransfer\Enums\ProductFilter;
use Webkul\DataTransfer\Enums\ProductStatusFilter;
use Webkul\DataTransfer\Helpers\Formatters\ScopeFilterValue;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Applies the product export profile filters to a query. The SQL export cursor
 * applies every filter natively; the Elasticsearch cursor consumes the resolver
 * helpers and offloads the value-based filters (completeness, custom attributes)
 * to {@see self::valueFilteredIds()}.
 */
class ProductExportFilter
{
    /**
     * Product attribute whose value lives on the products table column.
     */
    const COLUMN_ATTRIBUTE = 'sku';

    /**
     * Memoised active locale codes, resolved once per filter application.
     */
    private ?array $activeLocaleCodes = null;

    /**
     * Memoised channel codes, resolved once per filter application.
     */
    private ?array $activeChannelCodes = null;

    public function __construct(
        protected ChannelRepository $channelRepository,
        protected LocaleRepository $localeRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Applies every product filter to the given SQL query builder.
     */
    public function applyToQuery(Builder $query, array $filters): void
    {
        $this->applyStatus($query, $filters);
        $this->applySku($query, $filters);
        $this->applyAttributeFamilies($query, $filters);
        $this->applyCategories($query, $filters);
        $this->applyUpdatedAfter($query, $filters);
        $this->applyUpdatedBefore($query, $filters);
        $this->applyCompleteness($query, $filters);
        $this->applyCustomAttributes($query, $filters);
    }

    /**
     * Product ids matching the value-based filters (completeness, custom
     * attributes and the SKU list) that cannot be expressed natively in
     * Elasticsearch. Returns null when no such filter is active so the caller
     * can skip restriction.
     */
    public function valueFilteredIds(array $filters): ?array
    {
        $hasCompleteness = ! in_array(
            $filters[ProductFilter::COMPLETENESS->value] ?? null,
            [null, '', CompletenessCondition::NONE->value],
            true
        );

        $hasCustomAttributes = ! empty($this->parseCustomAttributes($filters[ProductFilter::CUSTOM_ATTRIBUTES->value] ?? null));

        $hasSkus = ! empty($this->skuValues($filters));

        if (! $hasCompleteness && ! $hasCustomAttributes && ! $hasSkus) {
            return null;
        }

        $query = $this->productRepository->getModel()->newQuery();

        $this->applySku($query, $filters);
        $this->applyCompleteness($query, $filters);
        $this->applyCustomAttributes($query, $filters);

        return $query->pluck('id')->all();
    }

    /**
     * Normalized SKU list from the comma and/or whitespace separated SKU
     * filter, or an empty array when no SKU filter is set.
     */
    public function skuValues(array $filters): array
    {
        $value = $filters[ProductFilter::SKU->value] ?? null;

        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($sku) => trim((string) $sku), $value)));
    }

    /**
     * Resolved attribute family ids for the selected family codes, or an empty
     * array when no family filter is set.
     */
    public function attributeFamilyIds(array $filters): array
    {
        $codes = ScopeFilterValue::toCodes($filters[ProductFilter::ATTRIBUTE_FAMILIES->value] ?? null);

        if (empty($codes)) {
            return [];
        }

        return $this->attributeFamilyRepository->findWhereIn('code', $codes)->pluck('id')->all();
    }

    /**
     * Selected category codes, or an empty array when no category filter is set.
     */
    public function categoryCodes(array $filters): array
    {
        return ScopeFilterValue::toCodes($filters[ProductFilter::CATEGORIES->value] ?? null);
    }

    /**
     * Resolved "updated since" date, or null when no time condition is set.
     */
    public function updatedAfter(array $filters): ?string
    {
        return $filters[ProductFilter::UPDATED_AFTER->value] ?? null;
    }

    /**
     * Resolved "updated until" date, or null when no upper bound is set.
     */
    public function updatedBefore(array $filters): ?string
    {
        return $filters[ProductFilter::UPDATED_BEFORE->value] ?? null;
    }

    /**
     * Resolved status flag (true/false), or null when no status filter is set
     * or when every status is requested.
     */
    public function statusValue(array $filters): ?bool
    {
        $status = $filters[ProductFilter::STATUS->value] ?? null;

        if (empty($status) || $status === ProductStatusFilter::ALL->value) {
            return null;
        }

        return $status === ProductStatusFilter::ENABLE->value;
    }

    protected function applyStatus(Builder $query, array $filters): void
    {
        $status = $this->statusValue($filters);

        if ($status === null) {
            return;
        }

        $query->where('status', $status);
    }

    protected function applySku(Builder $query, array $filters): void
    {
        $skus = $this->skuValues($filters);

        if (empty($skus)) {
            return;
        }

        $query->whereIn('sku', $skus);
    }

    protected function applyAttributeFamilies(Builder $query, array $filters): void
    {
        $codes = ScopeFilterValue::toCodes($filters[ProductFilter::ATTRIBUTE_FAMILIES->value] ?? null);

        if (empty($codes)) {
            return;
        }

        $query->whereIn('attribute_family_id', $this->attributeFamilyIds($filters));
    }

    protected function applyCategories(Builder $query, array $filters): void
    {
        $codes = $this->categoryCodes($filters);

        if (empty($codes)) {
            return;
        }

        $query->where(function (Builder $query) use ($codes) {
            foreach ($codes as $code) {
                $query->orWhereJsonContains('values->categories', $code);
            }
        });
    }

    protected function applyUpdatedAfter(Builder $query, array $filters): void
    {
        $date = $this->updatedAfter($filters);

        if (empty($date)) {
            return;
        }

        $query->where('updated_at', '>=', $date);
    }

    protected function applyUpdatedBefore(Builder $query, array $filters): void
    {
        $date = $this->updatedBefore($filters);

        if (empty($date)) {
            return;
        }

        $query->where('updated_at', '<=', $date);
    }

    protected function applyCompleteness(Builder $query, array $filters): void
    {
        $condition = $filters[ProductFilter::COMPLETENESS->value] ?? null;

        if (in_array($condition, [null, '', CompletenessCondition::NONE->value], true)) {
            return;
        }

        $channelIds = $this->resolveChannelIds($filters);
        $localeIds = $this->resolveLocaleIds($filters);

        if (empty($channelIds) || empty($localeIds)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $complete = fn ($scope) => $scope->whereIn('channel_id', $channelIds)
            ->whereIn('locale_id', $localeIds)
            ->where('score', '>=', CompletenessCondition::COMPLETE_SCORE);

        $incomplete = fn ($scope) => $scope->whereIn('channel_id', $channelIds)
            ->whereIn('locale_id', $localeIds)
            ->where('score', '<', CompletenessCondition::COMPLETE_SCORE);

        match ($condition) {
            CompletenessCondition::AT_LEAST_ONE->value => $query->whereHas('completenessScores', $complete),
            CompletenessCondition::ALL->value          => $query->whereHas('completenessScores', $complete)
                ->whereDoesntHave('completenessScores', $incomplete),
            default => null,
        };
    }

    protected function applyCustomAttributes(Builder $query, array $filters): void
    {
        $rows = $this->parseCustomAttributes($filters[ProductFilter::CUSTOM_ATTRIBUTES->value] ?? null);

        if (empty($rows)) {
            return;
        }

        $attributes = $this->attributesByCode($rows);

        foreach ($rows as $row) {
            $code = $row['attribute'] ?? null;

            if (empty($code)) {
                continue;
            }

            $operator = $row['operator'] ?? AttributeConditionOperators::IN;

            $attribute = $code === self::COLUMN_ATTRIBUTE ? null : $attributes->get($code);

            $this->applyCondition($query, $code, $attribute, $operator, $row['value'] ?? null, $row['value2'] ?? null);
        }
    }

    /**
     * Loads every attribute referenced by the condition rows in a single query,
     * keyed by code, so each row resolves without an extra round-trip.
     */
    protected function attributesByCode(array $rows): Collection
    {
        $codes = array_values(array_filter(
            array_map(fn ($row) => $row['attribute'] ?? null, $rows),
            fn ($code) => $code && $code !== self::COLUMN_ATTRIBUTE
        ));

        if (empty($codes)) {
            return collect();
        }

        return $this->attributeRepository->findWhereIn('code', array_unique($codes))->keyBy('code');
    }

    /**
     * Applies a single attribute condition, dispatching to the matcher for the
     * given operator. Conditions whose value is required but missing are skipped.
     */
    protected function applyCondition(Builder $query, string $code, ?Attribute $attribute, string $operator, mixed $value, mixed $value2): void
    {
        if (in_array($operator, [AttributeConditionOperators::EMPTY, AttributeConditionOperators::NOT_EMPTY], true)) {
            $this->applyEmptiness($query, $code, $attribute, $operator === AttributeConditionOperators::NOT_EMPTY);

            return;
        }

        if (in_array($operator, [AttributeConditionOperators::IN, AttributeConditionOperators::NOT_IN], true)) {
            $values = $this->normalizeFilterValues($value);

            if (! empty($values)) {
                $this->applyOptionMatch($query, $code, $attribute, $values, $operator === AttributeConditionOperators::NOT_IN);
            }

            return;
        }

        if ($operator === AttributeConditionOperators::BETWEEN) {
            if (! $this->isBlank($value) && ! $this->isBlank($value2)) {
                $this->applyBetween($query, $code, $attribute, $this->scalar($value), $this->scalar($value2));
            }

            return;
        }

        if ($this->isBlank($value)) {
            return;
        }

        if (in_array($operator, [
            AttributeConditionOperators::LESS_THAN,
            AttributeConditionOperators::LESS_THAN_EQUAL,
            AttributeConditionOperators::GREATER_THAN,
            AttributeConditionOperators::GREATER_THAN_EQUAL,
            AttributeConditionOperators::BEFORE,
            AttributeConditionOperators::AFTER,
        ], true)) {
            $this->applyComparison($query, $code, $attribute, $operator, $this->scalar($value));

            return;
        }

        if ($operator === AttributeConditionOperators::CONTAINS) {
            $this->applyContains($query, $code, $attribute, $this->scalar($value));

            return;
        }

        $this->applyOptionMatch($query, $code, $attribute, $this->normalizeFilterValues($value), false);
    }

    /**
     * "Is empty" / "Is not empty": the attribute has no value across any of its
     * scoped paths (or column, for the SKU).
     */
    protected function applyEmptiness(Builder $query, string $code, ?Attribute $attribute, bool $notEmpty): void
    {
        if ($code === self::COLUMN_ATTRIBUTE) {
            $notEmpty
                ? $query->whereNotNull($code)->where($code, '!=', '')
                : $query->where(fn (Builder $query) => $query->whereNull($code)->orWhere($code, ''));

            return;
        }

        $paths = $this->valuePaths($code, $attribute);

        $query->where(function (Builder $query) use ($paths, $notEmpty) {
            foreach ($paths as $path) {
                $notEmpty ? $query->orWhereNotNull($path) : $query->whereNull($path);
            }
        });
    }

    /**
     * "In list" / "Not in list" / "Equals": matches any of the option codes
     * inside the (optionally multi-value) attribute. "Not in list" also keeps
     * products that have no value at all.
     */
    protected function applyOptionMatch(Builder $query, string $code, ?Attribute $attribute, array $values, bool $negate): void
    {
        if (empty($values)) {
            return;
        }

        if ($code === self::COLUMN_ATTRIBUTE) {
            $negate ? $query->whereNotIn($code, $values) : $query->whereIn($code, $values);

            return;
        }

        $paths = $this->valuePaths($code, $attribute);

        $isMultiValue = in_array($attribute?->type, [
            Attribute::MULTISELECT_FIELD_TYPE,
            Attribute::CHECKBOX_FIELD_TYPE,
        ], true);

        $matches = function (Builder $query) use ($paths, $values, $isMultiValue) {
            foreach ($paths as $path) {
                foreach ($values as $value) {
                    if ($isMultiValue) {
                        $this->orWhereCommaListContains($query, $path, $value);

                        continue;
                    }

                    $query->orWhereJsonContains($path, $value);
                }
            }
        };

        if (! $negate) {
            $query->where($matches);

            return;
        }

        $query->where(function (Builder $query) use ($matches, $paths) {
            $query->whereNot($matches)->orWhere(function (Builder $query) use ($paths) {
                foreach ($paths as $path) {
                    $query->whereNull($path);
                }
            });
        });
    }

    /**
     * "Contains": substring match on the stored text value.
     */
    protected function applyContains(Builder $query, string $code, ?Attribute $attribute, mixed $value): void
    {
        $escaped = addcslashes((string) $value, '%_\\');

        if ($code === self::COLUMN_ATTRIBUTE) {
            $query->where($code, 'like', "%{$escaped}%");

            return;
        }

        $grammar = DB::rawQueryGrammar();

        $query->where(function (Builder $query) use ($code, $attribute, $grammar, $escaped) {
            foreach ($this->valuePathSegments($code, $attribute) as $segments) {
                $extract = $grammar->jsonExtract('values', ...$segments);

                $query->orWhereRaw("{$extract} LIKE ?", ["%{$escaped}%"]);
            }
        });
    }

    /**
     * "Less than" / "greater than" / "before" / "after": a single typed
     * comparison against the stored value.
     */
    protected function applyComparison(Builder $query, string $code, ?Attribute $attribute, string $operator, mixed $value): void
    {
        $sqlOperator = match ($operator) {
            AttributeConditionOperators::LESS_THAN, AttributeConditionOperators::BEFORE          => '<',
            AttributeConditionOperators::LESS_THAN_EQUAL                                         => '<=',
            AttributeConditionOperators::GREATER_THAN, AttributeConditionOperators::AFTER        => '>',
            AttributeConditionOperators::GREATER_THAN_EQUAL                                      => '>=',
            default                                                                              => '=',
        };

        if ($code === self::COLUMN_ATTRIBUTE) {
            $query->where($code, $sqlOperator, $value);

            return;
        }

        $this->applyTypedComparison($query, $code, $attribute, $this->isDateOperator($attribute, $operator), [[$sqlOperator, $value]]);
    }

    /**
     * "Between": an inclusive low/high comparison against the stored value.
     */
    protected function applyBetween(Builder $query, string $code, ?Attribute $attribute, mixed $low, mixed $high): void
    {
        if ($code === self::COLUMN_ATTRIBUTE) {
            $query->whereBetween($code, [$low, $high]);

            return;
        }

        $isDate = in_array($attribute?->type, AttributeConditionOperators::DATE_TYPES, true);

        $this->applyTypedComparison($query, $code, $attribute, $isDate, [['>=', $low], ['<=', $high]]);
    }

    /**
     * Applies numeric or date comparisons to the attribute's scoped JSON paths.
     * The CASE guard keeps the CAST from blowing up on non-numeric/non-date
     * values, which Postgres (unlike MySQL) treats as a fatal error.
     */
    protected function applyTypedComparison(Builder $query, string $code, ?Attribute $attribute, bool $isDate, array $comparisons): void
    {
        $grammar = DB::rawQueryGrammar();
        $regexOperator = $grammar->getRegexOperator();

        $castType = $isDate ? 'DATE' : 'DECIMAL(30,10)';
        $pattern = $isDate ? '^[0-9]{4}-[0-9]{2}-[0-9]{2}' : '^-?[0-9]+([.][0-9]+)?$';

        $query->where(function (Builder $query) use ($code, $attribute, $grammar, $regexOperator, $castType, $pattern, $comparisons) {
            foreach ($this->valuePathSegments($code, $attribute) as $segments) {
                $extract = $grammar->jsonExtract('values', ...$segments);
                $expr = "CASE WHEN {$extract} {$regexOperator} '{$pattern}' THEN CAST({$extract} AS {$castType}) END";

                $query->orWhere(function (Builder $query) use ($expr, $comparisons) {
                    foreach ($comparisons as [$sqlOperator, $value]) {
                        $query->whereRaw("{$expr} {$sqlOperator} ?", [$value]);
                    }
                });
            }
        });
    }

    /**
     * Whether the comparison should be treated as a date comparison.
     */
    protected function isDateOperator(?Attribute $attribute, string $operator): bool
    {
        return in_array($attribute?->type, AttributeConditionOperators::DATE_TYPES, true)
            || in_array($operator, [AttributeConditionOperators::BEFORE, AttributeConditionOperators::AFTER], true);
    }

    /**
     * First scalar of a (possibly list) value.
     */
    protected function scalar(mixed $value): mixed
    {
        return is_array($value) ? reset($value) : $value;
    }

    /**
     * Whether a condition value is effectively empty.
     */
    protected function isBlank(mixed $value): bool
    {
        if (is_array($value)) {
            return empty(array_filter($value, fn ($item) => trim((string) $item) !== ''));
        }

        return $value === null || trim((string) $value) === '';
    }

    /**
     * Matches a single option code inside a multi-value attribute, which is
     * stored as a comma separated string (e.g. "red,blue"), without matching
     * codes that only contain the searched code as a substring.
     */
    protected function orWhereCommaListContains(Builder $query, string $path, string $value): void
    {
        $escaped = addcslashes($value, '%_\\');

        $query->orWhere(function (Builder $query) use ($path, $value, $escaped) {
            $query->where($path, $value)
                ->orWhere($path, 'like', "{$escaped},%")
                ->orWhere($path, 'like', "%,{$escaped},%")
                ->orWhere($path, 'like', "%,{$escaped}");
        });
    }

    /**
     * Normalizes a custom attribute filter value (scalar or list) into a flat
     * list of non-empty string values.
     */
    protected function normalizeFilterValues(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            array_map(fn ($value) => trim((string) $value), $values),
            fn ($value) => $value !== ''
        ));
    }

    /**
     * JSON paths under the product `values` column where the attribute value may
     * be stored, derived from the attribute's locale/channel scoping.
     */
    protected function valuePaths(string $code, ?Attribute $attribute): array
    {
        $paths = ["values->common->{$code}"];

        if (! $attribute) {
            return $paths;
        }

        $localized = (bool) $attribute->value_per_locale;
        $scoped = (bool) $attribute->value_per_channel;

        $locales = $this->activeLocaleCodes();
        $channels = $this->activeChannelCodes();

        if ($localized && $scoped) {
            foreach ($channels as $channel) {
                foreach ($locales as $locale) {
                    $paths[] = "values->channel_locale_specific->{$channel}->{$locale}->{$code}";
                }
            }
        } elseif ($localized) {
            foreach ($locales as $locale) {
                $paths[] = "values->locale_specific->{$locale}->{$code}";
            }
        } elseif ($scoped) {
            foreach ($channels as $channel) {
                $paths[] = "values->channel_specific->{$channel}->{$code}";
            }
        }

        return $paths;
    }

    /**
     * Same scoped locations as {@see self::valuePaths()} but expressed as path
     * segment arrays (without the leading `values` column), ready for the
     * cross-database JSON extract grammar helper used by the typed comparison
     * and contains matchers.
     */
    protected function valuePathSegments(string $code, ?Attribute $attribute): array
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $code)) {
            return [];
        }

        $segments = [['common', $code]];

        if (! $attribute) {
            return $segments;
        }

        $localized = (bool) $attribute->value_per_locale;
        $scoped = (bool) $attribute->value_per_channel;

        $locales = $this->activeLocaleCodes();
        $channels = $this->activeChannelCodes();

        if ($localized && $scoped) {
            foreach ($channels as $channel) {
                foreach ($locales as $locale) {
                    $segments[] = ['channel_locale_specific', $channel, $locale, $code];
                }
            }
        } elseif ($localized) {
            foreach ($locales as $locale) {
                $segments[] = ['locale_specific', $locale, $code];
            }
        } elseif ($scoped) {
            foreach ($channels as $channel) {
                $segments[] = ['channel_specific', $channel, $code];
            }
        }

        return $segments;
    }

    protected function resolveChannelIds(array $filters): array
    {
        $codes = ScopeFilterValue::toCodes($filters[ProductExportScope::CHANNELS->value] ?? null);

        if (empty($codes)) {
            return $this->channelRepository->all()->pluck('id')->all();
        }

        return $this->channelRepository->findWhereIn('code', $codes)->pluck('id')->all();
    }

    protected function resolveLocaleIds(array $filters): array
    {
        $codes = ScopeFilterValue::toCodes($filters[ProductExportScope::LOCALES->value] ?? null);

        if (empty($codes)) {
            return $this->localeRepository->getActiveLocales()->pluck('id')->all();
        }

        return $this->localeRepository->findWhereIn('code', $codes)->pluck('id')->all();
    }

    protected function activeLocaleCodes(): array
    {
        return $this->activeLocaleCodes ??= $this->localeRepository->getActiveLocales()->pluck('code')->all();
    }

    protected function activeChannelCodes(): array
    {
        return $this->activeChannelCodes ??= $this->channelRepository->all()->pluck('code')->all();
    }

    /**
     * Normalizes the stored custom attribute filters into a list of
     * ['attribute' => code, 'value' => value] rows.
     */
    protected function parseCustomAttributes(mixed $value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($row) => is_array($row) && ! empty($row['attribute'])));
    }
}

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

class ProductExportFilter
{
    const COLUMN_ATTRIBUTE = 'sku';

    private ?array $activeLocaleCodes = null;

    private ?array $activeChannelCodes = null;

    public function __construct(
        protected ChannelRepository $channelRepository,
        protected LocaleRepository $localeRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
        protected ProductRepository $productRepository,
    ) {}

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

    public function valueFilteredIds(array $filters): ?array
    {
        $hasCompleteness = ! in_array(
            $filters[ProductFilter::COMPLETENESS->value] ?? null,
            [null, '', CompletenessCondition::NONE->value],
            true
        );

        $hasCustomAttributes = $this->parseCustomAttributes($filters[ProductFilter::CUSTOM_ATTRIBUTES->value] ?? null) !== [];

        $hasSkus = $this->skuValues($filters) !== [];

        if (! $hasCompleteness && ! $hasCustomAttributes && ! $hasSkus) {
            return null;
        }

        $query = $this->productRepository->getModel()->newQuery();

        $this->applySku($query, $filters);
        $this->applyCompleteness($query, $filters);
        $this->applyCustomAttributes($query, $filters);

        return $query->pluck('id')->all();
    }

    public function skuValues(array $filters): array
    {
        $value = $filters[ProductFilter::SKU->value] ?? null;

        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($sku): string => trim((string) $sku), $value)));
    }

    public function attributeFamilyIds(array $filters): array
    {
        $codes = ScopeFilterValue::toCodes($filters[ProductFilter::ATTRIBUTE_FAMILIES->value] ?? null);

        if ($codes === []) {
            return [];
        }

        return $this->attributeFamilyRepository->findWhereIn('code', $codes)->pluck('id')->all();
    }

    public function categoryCodes(array $filters): array
    {
        return ScopeFilterValue::toCodes($filters[ProductFilter::CATEGORIES->value] ?? null);
    }

    public function updatedAfter(array $filters): ?string
    {
        return $filters[ProductFilter::UPDATED_AFTER->value] ?? null;
    }

    public function updatedBefore(array $filters): ?string
    {
        return $filters[ProductFilter::UPDATED_BEFORE->value] ?? null;
    }

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

        if ($skus === []) {
            return;
        }

        $query->whereIn('sku', $skus);
    }

    protected function applyAttributeFamilies(Builder $query, array $filters): void
    {
        $codes = ScopeFilterValue::toCodes($filters[ProductFilter::ATTRIBUTE_FAMILIES->value] ?? null);

        if ($codes === []) {
            return;
        }

        $query->whereIn('attribute_family_id', $this->attributeFamilyIds($filters));
    }

    protected function applyCategories(Builder $query, array $filters): void
    {
        $codes = $this->categoryCodes($filters);

        if ($codes === []) {
            return;
        }

        $query->where(function (Builder $query) use ($codes): void {
            foreach ($codes as $code) {
                $query->orWhereJsonContains('values->categories', $code);
            }
        });
    }

    protected function applyUpdatedAfter(Builder $query, array $filters): void
    {
        $date = $this->updatedAfter($filters);

        if (in_array($date, [null, '', '0'], true)) {
            return;
        }

        $query->where('updated_at', '>=', $date);
    }

    protected function applyUpdatedBefore(Builder $query, array $filters): void
    {
        $date = $this->updatedBefore($filters);

        if (in_array($date, [null, '', '0'], true)) {
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

        if ($channelIds === [] || $localeIds === []) {
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

        if ($rows === []) {
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

    protected function attributesByCode(array $rows): Collection
    {
        $codes = array_values(array_filter(
            array_map(fn (array $row) => $row['attribute'] ?? null, $rows),
            fn ($code): bool => $code && $code !== self::COLUMN_ATTRIBUTE
        ));

        if ($codes === []) {
            return collect();
        }

        return $this->attributeRepository->findWhereIn('code', array_unique($codes))->keyBy('code');
    }

    protected function applyCondition(Builder $query, string $code, ?Attribute $attribute, string $operator, mixed $value, mixed $value2): void
    {
        if (in_array($operator, [AttributeConditionOperators::EMPTY, AttributeConditionOperators::NOT_EMPTY], true)) {
            $this->applyEmptiness($query, $code, $attribute, $operator === AttributeConditionOperators::NOT_EMPTY);

            return;
        }

        if (in_array($operator, [AttributeConditionOperators::IN, AttributeConditionOperators::NOT_IN], true)) {
            $values = $this->normalizeFilterValues($value);

            if ($values !== []) {
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

    protected function applyEmptiness(Builder $query, string $code, ?Attribute $attribute, bool $notEmpty): void
    {
        if ($code === self::COLUMN_ATTRIBUTE) {
            $notEmpty
                ? $query->whereNotNull($code)->where($code, '!=', '')
                : $query->where(fn (Builder $query) => $query->whereNull($code)->orWhere($code, ''));

            return;
        }

        $paths = $this->valuePaths($code, $attribute);

        $query->where(function (Builder $query) use ($paths, $notEmpty): void {
            foreach ($paths as $path) {
                $notEmpty ? $query->orWhereNotNull($path) : $query->whereNull($path);
            }
        });
    }

    protected function applyOptionMatch(Builder $query, string $code, ?Attribute $attribute, array $values, bool $negate): void
    {
        if ($values === []) {
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

        $matches = function (Builder $query) use ($paths, $values, $isMultiValue): void {
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

        $query->where(function (Builder $query) use ($matches, $paths): void {
            $query->whereNot($matches)->orWhere(function (Builder $query) use ($paths): void {
                foreach ($paths as $path) {
                    $query->whereNull($path);
                }
            });
        });
    }

    protected function applyContains(Builder $query, string $code, ?Attribute $attribute, mixed $value): void
    {
        $escaped = addcslashes((string) $value, '%_\\');

        if ($code === self::COLUMN_ATTRIBUTE) {
            $query->where($code, 'like', "%{$escaped}%");

            return;
        }

        $grammar = DB::rawQueryGrammar();

        $query->where(function (Builder $query) use ($code, $attribute, $grammar, $escaped): void {
            foreach ($this->valuePathSegments($code, $attribute) as $segments) {
                $extract = $grammar->jsonExtract('values', ...$segments);

                $query->orWhereRaw("{$extract} LIKE ?", ["%{$escaped}%"]);
            }
        });
    }

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

    protected function applyBetween(Builder $query, string $code, ?Attribute $attribute, mixed $low, mixed $high): void
    {
        if ($code === self::COLUMN_ATTRIBUTE) {
            $query->whereBetween($code, [$low, $high]);

            return;
        }

        $isDate = in_array($attribute?->type, AttributeConditionOperators::DATE_TYPES, true);

        $this->applyTypedComparison($query, $code, $attribute, $isDate, [['>=', $low], ['<=', $high]]);
    }

    protected function applyTypedComparison(Builder $query, string $code, ?Attribute $attribute, bool $isDate, array $comparisons): void
    {
        $grammar = DB::rawQueryGrammar();
        $regexOperator = $grammar->getRegexOperator();

        $castType = $isDate ? 'DATE' : 'DECIMAL(30,10)';
        $pattern = $isDate ? '^[0-9]{4}-[0-9]{2}-[0-9]{2}' : '^-?[0-9]+([.][0-9]+)?$';

        $query->where(function (Builder $query) use ($code, $attribute, $grammar, $regexOperator, $castType, $pattern, $comparisons): void {
            foreach ($this->valuePathSegments($code, $attribute) as $segments) {
                $extract = $grammar->jsonExtract('values', ...$segments);
                $expr = "CASE WHEN {$extract} {$regexOperator} '{$pattern}' THEN CAST({$extract} AS {$castType}) END";

                $query->orWhere(function (Builder $query) use ($expr, $comparisons): void {
                    foreach ($comparisons as [$sqlOperator, $value]) {
                        $query->whereRaw("{$expr} {$sqlOperator} ?", [$value]);
                    }
                });
            }
        });
    }

    protected function isDateOperator(?Attribute $attribute, string $operator): bool
    {
        return in_array($attribute?->type, AttributeConditionOperators::DATE_TYPES, true)
            || in_array($operator, [AttributeConditionOperators::BEFORE, AttributeConditionOperators::AFTER], true);
    }

    protected function scalar(mixed $value): mixed
    {
        return is_array($value) ? reset($value) : $value;
    }

    protected function isBlank(mixed $value): bool
    {
        if (is_array($value)) {
            return array_filter($value, fn ($item): bool => trim((string) $item) !== '') === [];
        }

        return $value === null || trim((string) $value) === '';
    }

    protected function orWhereCommaListContains(Builder $query, string $path, string $value): void
    {
        $escaped = addcslashes($value, '%_\\');

        $query->orWhere(function (Builder $query) use ($path, $value, $escaped): void {
            $query->where($path, $value)
                ->orWhere($path, 'like', "{$escaped},%")
                ->orWhere($path, 'like', "%,{$escaped},%")
                ->orWhere($path, 'like', "%,{$escaped}");
        });
    }

    protected function normalizeFilterValues(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            array_map(fn ($value): string => trim((string) $value), $values),
            fn (string $value): bool => $value !== ''
        ));
    }

    protected function valuePaths(string $code, ?Attribute $attribute): array
    {
        $paths = ["values->common->{$code}"];

        if (! $attribute instanceof Attribute) {
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

    protected function valuePathSegments(string $code, ?Attribute $attribute): array
    {
        if (! preg_match('/^\w+$/', $code)) {
            return [];
        }

        $segments = [['common', $code]];

        if (! $attribute instanceof Attribute) {
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

        if ($codes === []) {
            return $this->channelRepository->all()->pluck('id')->all();
        }

        return $this->channelRepository->findWhereIn('code', $codes)->pluck('id')->all();
    }

    protected function resolveLocaleIds(array $filters): array
    {
        $codes = ScopeFilterValue::toCodes($filters[ProductExportScope::LOCALES->value] ?? null);

        if ($codes === []) {
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

        return array_values(array_filter($value, fn ($row): bool => is_array($row) && ! empty($row['attribute'])));
    }
}

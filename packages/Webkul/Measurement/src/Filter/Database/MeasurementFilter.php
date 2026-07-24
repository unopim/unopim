<?php

namespace Webkul\Measurement\Filter\Database;

use Illuminate\Support\Facades\DB;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Product\Filter\Database\AbstractDatabaseAttributeFilter;

/**
 * Measurement attribute filter for a database query.
 *
 * Measurement values are stored as { unit, amount, family, base_data, base_unit,
 * symbol }. Comparisons run against `base_data`, the amount normalised to the
 * family's standard unit, so a product saved in centimetres still matches a
 * filter expressed in metres. The published operator set mirrors master's
 * numeric attribute filter.
 */
class MeasurementFilter extends AbstractDatabaseAttributeFilter
{
    public function __construct(
        array $supportedAttributeTypes = ['measurement'],
        array $allowedOperators = [
            FilterOperators::EQUAL,
            FilterOperators::LESS_THAN,
            FilterOperators::LESS_THAN_OR_EQUAL,
            FilterOperators::GREATER_THAN,
            FilterOperators::GREATER_THAN_OR_EQUAL,
            FilterOperators::RANGE,
            FilterOperators::IS_EMPTY,
            FilterOperators::IS_NOT_EMPTY,
        ]
    ) {
        $this->supportedAttributeTypes = $supportedAttributeTypes;
        $this->allowedOperators = $allowedOperators;
    }

    /**
     * {@inheritdoc}
     */
    public function addAttributeFilter(
        $attribute,
        $operator,
        $value,
        ?string $locale = null,
        ?string $channel = null,
        array $options = []
    ): static {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        $grammar = DB::rawQueryGrammar();
        $tablePath = $this->getSearchTablePath($options);
        $scopedPath = $this->getScopedAttributePath($attribute, $locale, $channel);

        $basePath = $grammar->jsonExtract($tablePath, ...array_merge($scopedPath, ['base_data']));

        if ($operator === FilterOperators::IS_EMPTY) {
            $this->queryBuilder->whereRaw("$basePath IS NULL");

            return $this;
        }

        if ($operator === FilterOperators::IS_NOT_EMPTY) {
            $this->queryBuilder->whereRaw("$basePath IS NOT NULL");

            return $this;
        }

        [$unit, $amounts] = $this->parseValue($value);

        if (empty($amounts) && empty($unit)) {
            return $this;
        }

        $unitPath = $grammar->jsonExtract($tablePath, ...array_merge($scopedPath, ['unit']));

        $baseColumn = "CAST($basePath AS DECIMAL(20,6))";

        $bases = $this->toBaseValues($amounts, $unit, $attribute);

        if ($bases === []) {
            $this->queryBuilder->whereRaw("$unitPath = ?", [$unit]);

            return $this;
        }

        match ($operator) {
            FilterOperators::GREATER_THAN          => $this->queryBuilder->whereRaw("$baseColumn > ?", [$bases[0]]),
            FilterOperators::GREATER_THAN_OR_EQUAL => $this->queryBuilder->whereRaw("$baseColumn >= ?", [$bases[0]]),
            FilterOperators::LESS_THAN             => $this->queryBuilder->whereRaw("$baseColumn < ?", [$bases[0]]),
            FilterOperators::LESS_THAN_OR_EQUAL    => $this->queryBuilder->whereRaw("$baseColumn <= ?", [$bases[0]]),
            FilterOperators::RANGE                 => $this->applyRange($baseColumn, $bases),
            default                                => $this->queryBuilder->whereRaw("$baseColumn = ?", [$bases[0]]),
        };

        return $this;
    }

    /**
     * Split the incoming filter value into a unit and one or more amounts.
     *
     * The generic filter UI sends [unit, amount] for single-value operators and
     * [unit, amountFrom, amountTo] for the range operator.
     */
    protected function parseValue($value): array
    {
        if (! is_array($value)) {
            return [null, $this->cleanAmounts([$value])];
        }

        $unit = $value[0] ?? null;

        $amounts = array_slice($value, 1);

        if (count($amounts) === 1 && is_array($amounts[0])) {
            $amounts = $amounts[0];
        }

        return [$unit, $this->cleanAmounts($amounts)];
    }

    /**
     * Drop empty entries and keep only numeric amounts.
     */
    protected function cleanAmounts(array $amounts): array
    {
        return array_values(array_filter(
            $amounts,
            fn ($amount): bool => $amount !== null && $amount !== '' && is_numeric($amount)
        ));
    }

    /**
     * Convert each filter amount from the selected unit into the family's
     * standard unit, so it can be compared against the stored base value.
     */
    protected function toBaseValues(array $amounts, ?string $unit, $attribute): array
    {
        if ($amounts === []) {
            return [];
        }

        if (in_array($unit, [null, '', '0'], true)) {
            return array_map(fn ($amount): float => (float) $amount, $amounts);
        }

        $measurement = resolve(AttributeMeasurementRepository::class)->getByAttributeId($attribute->id);

        if (! $measurement || ! $measurement->family) {
            return array_map(fn ($amount): float => (float) $amount, $amounts);
        }

        $helper = resolve(MeasurementHelper::class);

        return array_map(
            fn ($amount): float => (float) $helper->calculateBaseValue($amount, $unit, $measurement->family),
            $amounts
        );
    }

    /**
     * Apply a between comparison, ordering the bounds regardless of input order.
     */
    protected function applyRange(string $column, array $bases): void
    {
        if (count($bases) < 2) {
            $this->queryBuilder->whereRaw("$column = ?", [$bases[0]]);

            return;
        }

        $this->queryBuilder->whereRaw(
            "$column BETWEEN ? AND ?",
            [min($bases[0], $bases[1]), max($bases[0], $bases[1])]
        );
    }
}

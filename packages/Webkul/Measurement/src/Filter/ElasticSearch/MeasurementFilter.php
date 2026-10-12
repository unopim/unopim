<?php

namespace Webkul\Measurement\Filter\ElasticSearch;

use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Product\Filter\ElasticSearch\AbstractElasticSearchAttributeFilter;

/**
 * Measurement attribute filter for an Elasticsearch query.
 *
 * Mirrors the database filter: comparisons run against the indexed `base_data`
 * (the amount normalised to the family's standard unit) so a value stored in one
 * unit still matches a filter expressed in another. The published operator set
 * mirrors master's numeric attribute filter.
 */
class MeasurementFilter extends AbstractElasticSearchAttributeFilter
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
        $options = []
    ): static {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        $field = sprintf('%s.base_data', $this->getScopedAttributePath($attribute, $locale, $channel));

        if ($operator === FilterOperators::IS_EMPTY) {
            $this->queryBuilder::whereNot(['exists' => ['field' => $field]]);

            return $this;
        }

        if ($operator === FilterOperators::IS_NOT_EMPTY) {
            $this->queryBuilder::where(['exists' => ['field' => $field]]);

            return $this;
        }

        $unit = $value[0] ?? null;

        if (! isset($value[1]) || ! is_numeric($value[1])) {
            return $this;
        }

        $from = $this->toBaseValue($value[1], $unit, $attribute);

        match ($operator) {
            FilterOperators::EQUAL => $this->queryBuilder::where([
                'term' => [$field => $from],
            ]),
            FilterOperators::LESS_THAN, FilterOperators::LESS_THAN_OR_EQUAL, FilterOperators::GREATER_THAN, FilterOperators::GREATER_THAN_OR_EQUAL => $this->queryBuilder::where([
                'range' => [$field => [$operator->value => $from]],
            ]),
            FilterOperators::RANGE => $this->applyRange($field, $from, isset($value[2]) && is_numeric($value[2]) ? $this->toBaseValue($value[2], $unit, $attribute) : $from),
            default                => $this->queryBuilder,
        };

        return $this;
    }

    /**
     * Add a range query with ordered bounds.
     */
    protected function applyRange(string $field, float $from, float $to): void
    {
        $this->queryBuilder::where([
            'range' => [$field => ['gte' => min($from, $to), 'lte' => max($from, $to)]],
        ]);
    }

    /**
     * Convert a filter amount from the selected unit into the family's standard
     * unit, matching how `base_data` is stored.
     */
    protected function toBaseValue(mixed $amount, ?string $unit, $attribute): float
    {
        if (in_array($unit, [null, '', '0'], true)) {
            return (float) $amount;
        }

        $measurement = resolve(AttributeMeasurementRepository::class)->getByAttributeId($attribute->id);

        if (! $measurement || ! $measurement->family) {
            return (float) $amount;
        }

        return (float) resolve(MeasurementHelper::class)->calculateBaseValue($amount, $unit, $measurement->family);
    }
}

<?php

namespace Webkul\Measurement\Filter\Database;

use Illuminate\Support\Facades\DB;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\Database\AbstractDatabaseAttributeFilter;

/**
 * Measurement attribute filter for a database query.
 *
 * Measurement values are stored as { unit, amount, family, base_data, base_unit }.
 * The reused price-style filter UI sends the value as [unitCode, amount], so we
 * match the product on the same unit and the exact amount.
 */
class MeasurementFilter extends AbstractDatabaseAttributeFilter
{
    public function __construct(
        array $supportedAttributeTypes = ['measurement'],
        array $allowedOperators = [FilterOperators::IN, FilterOperators::EQUAL]
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
        $locale = null,
        $channel = null,
        $options = []
    ) {
        if ($this->queryBuilder === null) {
            throw new \LogicException('The search query builder is not initialized in the filter.');
        }

        // Value arrives as [unitCode, amount] from the reused price-style filter UI.
        $unit = is_array($value) ? ($value[0] ?? null) : null;
        $amount = is_array($value) ? ($value[1] ?? null) : $value;

        $hasAmount = $amount !== null && $amount !== '';

        if (! $hasAmount && empty($unit)) {
            return $this;
        }

        $scopedPath = $this->getScopedAttributePath($attribute, $locale, $channel);

        $grammar = DB::rawQueryGrammar();
        $tablePath = $this->getSearchTablePath($options);

        // jsonExtract already wraps in JSON_UNQUOTE, so values compare as plain strings/numbers.
        $unitPath = $grammar->jsonExtract($tablePath, ...array_merge($scopedPath, ['unit']));
        $amountPath = $grammar->jsonExtract($tablePath, ...array_merge($scopedPath, ['amount']));

        if (! empty($unit)) {
            $this->queryBuilder->whereRaw("$unitPath = ?", [$unit]);
        }

        if ($hasAmount) {
            $this->queryBuilder->whereRaw("CAST($amountPath AS DECIMAL(20,6)) = ?", [$amount]);
        }

        return $this;
    }
}

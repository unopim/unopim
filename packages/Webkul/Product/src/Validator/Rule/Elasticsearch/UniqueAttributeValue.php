<?php

namespace Webkul\Product\Validator\Rule\Elasticsearch;

use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\ElasticSearch\Facades\ElasticSearchQuery;
use Webkul\Product\Builders\ElasticProductQueryBuilder;
use Webkul\Product\Factories\ElasticSearch\Cursor\ResultCursorFactory;

class UniqueAttributeValue implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(protected string $attributeCode, protected ?int $productId = null) {}

    /**
     * Validate the attribute.
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $queryBuilder = app(ElasticProductQueryBuilder::class);

        $value = ! is_array($value) ? [$value] : $value;

        $queryBuilder->applyFilter($this->attributeCode, FilterOperators::IN, $value);

        if ($this->productId) {
            $queryBuilder->applyFilter('product_id', FilterOperators::NOT_EQUAL, $this->productId);
        }

        $esQuery = ElasticSearchQuery::build();

        $results = ResultCursorFactory::createCursor($esQuery, ['pagination' => ['per_page' => 1]]);

        if (count($results->getAllIds()) > 0) {
            $fail(trans('validation.unique'));
        }
    }
}

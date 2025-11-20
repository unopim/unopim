<?php

namespace Webkul\Product\Filter\Database;

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Services\AttributeService;
use Webkul\ElasticSearch\QueryString;

/**
 * Sku or name filter for an Elasticsearch query
 */
class SkuOrUniversalFilter extends AbstractDatabaseAttributeFilter
{
    public function __construct(
        protected AttributeService $attributeService,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function applyUnfilteredFilter(
        $fields,
        $operator,
        $value,
        $options = []
    ) {
        if ($this->queryBuilder === null) {
            throw new \LogicException('The search query builder is not initialized in the filter.');
        }

        foreach ($fields as $attribute) {
            $attribute = $this->attributeService->findAttributeByCode($attribute);
            if (! $attribute) {
                continue;
            }

            $locale = $attribute->value_per_locale ? $options['locale'] : null;
            $channel = $attribute->value_per_channel ? $options['channel'] : null;

            $attributePath = $this->getScopedAttributePath($attribute, $locale, $channel);

            $escapedValue = QueryString::escapeValue(current((array) $value));

            $searchPath = DB::rawQueryGrammar()->jsonExtract($this->getSearchTablePath($options), ...$attributePath);

            $this->queryBuilder->orWhereRaw(
                sprintf("LOWER($searchPath) LIKE ?"),
                "%$escapedValue%"
            );
        }

        return $this;
    }
}

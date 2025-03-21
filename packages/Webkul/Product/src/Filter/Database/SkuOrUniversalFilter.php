<?php

namespace Webkul\Product\Filter\Database;

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

            $this->queryBuilder->orWhereRaw(
                sprintf("LOWER(JSON_UNQUOTE(JSON_EXTRACT(%s, '%s'))) LIKE ?", $this->getSearchTablePath($options), $attributePath),
                "%$escapedValue%"
            );
        }

        return $this;
    }
}

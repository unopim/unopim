<?php

namespace Webkul\Product\Filter\Database;

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Contracts\Attribute;
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
        array $fields,
        mixed $operator,
        mixed $value,
        array $options = []
    ): static {
        if ($this->queryBuilder === null) {
            throw new \LogicException('The search query builder is not initialized in the filter.');
        }

        $escapedValue = QueryString::escapeValue(current((array) $value));

        $this->queryBuilder->where(function (mixed $query) use ($fields, $options, $escapedValue) {
            foreach ($fields as $attribute) {
                $attribute = $this->attributeService->findAttributeByCode($attribute);
                if (! $attribute instanceof Attribute) {
                    continue;
                }

                $locale = $attribute->value_per_locale ? $options['locale'] : null;
                $channel = $attribute->value_per_channel ? $options['channel'] : null;

                $attributePath = $this->getScopedAttributePath($attribute, $locale, $channel);

                $searchPath = DB::rawQueryGrammar()->jsonExtract($this->getSearchTablePath($options), ...$attributePath);

                $query->orWhereRaw(
                    "LOWER($searchPath) LIKE ?",
                    '%'.strtolower($escapedValue).'%'
                );
            }
        });

        return $this;
    }
}

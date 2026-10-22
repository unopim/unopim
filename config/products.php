<?php

return [
    /**
     * Skip attribute during product copy.
     *
     * Supported Relations: ['categories', 'inventories', 'customer_group_prices', 'images', 'videos', 'product_relations']
     *
     * Support Attributes: All Attributes (Example: 'sku', 'product_number', etc)
     */
    'copy' => [
        'skip_attributes' => [],
    ],

    /**
     * Attribute codes the product grid's quick search box looks in.
     *
     * Defaults to the SKU and the name. Add identifier attributes such as an
     * EAN, a GTIN or a supplier article number to make a product findable by
     * them from the one search box (Example: ['sku', 'name', 'ean',
     * 'supplier_article_number']). An attribute marked `is_filterable` can
     * already be filtered on its own grid column with the `contains`
     * operator; listing it here saves adding the column and picking the
     * operator, and works for attributes that are not filterable as well --
     * `is_filterable` is deliberately not consulted.
     *
     * Applies to the product grid's quick search box only. The association and
     * variant product pickers (`admin.catalog.products.search`) still match
     * the SKU column.
     *
     * Only text attributes are searched, because the quick search is a text
     * search: Elasticsearch maps price as float and date/datetime as date, and
     * the option backed types store the option code rather than the label that
     * was typed. textarea is excluded as well, because a long text attribute
     * matches most of the catalogue and the grid orders by its sort column
     * rather than by relevance, so the row that was meant would not come
     * first. Codes resolving to anything else, or to no attribute at all, are
     * skipped, and the SKU and the name are searched when none of them is
     * left.
     *
     * At most ten codes are used. The order given here is kept, except that
     * the SKU and the name are moved to the front so a long list cannot push
     * them out. The cap matters most without Elasticsearch: on the database
     * path every code adds a JSON extraction and a leading-wildcard LIKE per
     * product to a query no index can serve, while on Elasticsearch it adds
     * one more clause to a query that is already indexed.
     *
     * The two engines do not match alike: Elasticsearch matches from the start
     * of a word (match_phrase_prefix), the database matches anywhere in the
     * value (LIKE '%term%'). The last six digits of an EAN therefore find the
     * product without Elasticsearch and find nothing with it; the full
     * identifier finds it on both.
     *
     * On installations that cache the configuration (`php artisan
     * config:cache`, `php artisan optimize`), run `php artisan config:clear`
     * or re-cache after changing this list, otherwise the previous value stays
     * in effect.
     */
    'search_fields' => ['sku', 'name'],
];

<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Services\AttributeService;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\Database\SkuOrUniversalFilter;

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);
});

describe('SkuOrUniversalFilter groups OR conditions correctly when combined with other filters', function () {

    it('wraps multiple OR conditions in a WHERE group so AND filters are not broken', function () {
        $attributeService = app(AttributeService::class);

        $filter = new SkuOrUniversalFilter($attributeService);

        $qb = DB::table('products')->whereIn('products.status', ['1']);

        $filter->setQueryManager($qb);

        $filter->applyUnfilteredFilter(
            ['sku', 'name'],
            FilterOperators::WILDCARD,
            'test',
            ['locale' => 'en_US', 'channel' => 'default']
        );

        $sql = $qb->toSql();

        // Without the fix, the SQL is:
        //   WHERE "products"."status" IN (?) OR LOWER(...sku...) LIKE ? OR LOWER(...name...) LIKE ?
        // This incorrectly allows rows that match sku OR name regardless of status,
        // because OR has lower precedence than AND.
        //
        // With the fix the SQL must be:
        //   WHERE "products"."status" IN (?) AND (LOWER(...sku...) LIKE ? OR LOWER(...name...) LIKE ?)
        //
        // We detect this by checking that the OR conditions are inside a parenthesised group
        // that is connected to the outer conditions with AND (not at the top level).
        expect($sql)->toContain('and (');
        expect($sql)->not->toMatch('/\) or lower/i');
    });

    it('produces correct SQL even when "all" search is the only filter', function () {
        $attributeService = app(AttributeService::class);

        $filter = new SkuOrUniversalFilter($attributeService);

        $qb = DB::table('products');

        $filter->setQueryManager($qb);

        $filter->applyUnfilteredFilter(
            ['sku', 'name'],
            FilterOperators::WILDCARD,
            'hello',
            ['locale' => 'en_US', 'channel' => 'default']
        );

        $sql = $qb->toSql();

        // Even with no preceding filters, both sku and name conditions must appear
        expect(strtolower($sql))->toContain('lower(');
        expect(strtolower($sql))->toContain('like ?');
    });
});

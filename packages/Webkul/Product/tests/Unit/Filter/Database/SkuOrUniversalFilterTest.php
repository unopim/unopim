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

describe('SkuOrUniversalFilter refuses to answer a search it cannot evaluate', function () {

    it('matches no products when not one field resolves, instead of returning every product', function () {
        $filter = new SkuOrUniversalFilter(app(AttributeService::class));

        $qb = DB::table('products');

        $filter->setQueryManager($qb);

        $filter->applyUnfilteredFilter(
            ['no_such_attribute_code'],
            FilterOperators::WILDCARD,
            'test',
            ['locale' => 'en_US', 'channel' => 'default']
        );

        // An empty nested group would be dropped and the term silently ignored.
        expect($qb->toSql())->toContain('1 = 0');
        expect(strtolower($qb->toSql()))->not->toContain('like ?');
    });

    it('still searches the fields that do resolve when another code does not', function () {
        $filter = new SkuOrUniversalFilter(app(AttributeService::class));

        $qb = DB::table('products');

        $filter->setQueryManager($qb);

        $filter->applyUnfilteredFilter(
            ['sku', 'no_such_attribute_code'],
            FilterOperators::WILDCARD,
            'test',
            ['locale' => 'en_US', 'channel' => 'default']
        );

        // One resolved field is enough; the refusal must not fire.
        expect(substr_count(strtolower($qb->toSql()), 'like ?'))->toBe(1);
        expect($qb->toSql())->not->toContain('1 = 0');
    });
});

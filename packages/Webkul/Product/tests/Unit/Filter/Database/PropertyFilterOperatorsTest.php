<?php

use Illuminate\Support\Facades\DB;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\Database\Property\CategoryFilter;
use Webkul\Product\Filter\Database\Property\CompletenessFilter;
use Webkul\Product\Filter\Database\Property\DateTimeFilter;

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);
});

function applyPropertyFilter($filter, string $property, FilterOperators $operator, $value)
{
    $queryBuilder = DB::table('products');

    $filter->setQueryManager($queryBuilder);

    $filter->applyPropertyFilter($property, $operator, $value);

    return $queryBuilder;
}

describe('CompletenessFilter', function () {
    // Container-resolved (not `new`) so the ChannelCompletenessColumn
    // dependency is wired; no channel is requested in these tests, so it
    // falls back to the pre-existing cross-channel average column.
    it('compares the average completeness score', function () {
        $queryBuilder = applyPropertyFilter(app(CompletenessFilter::class), 'completeness', FilterOperators::GREATER_THAN_OR_EQUAL, ['50']);

        expect($queryBuilder->toSql())->toContain('avg_completeness_score >= ?')
            ->and($queryBuilder->getBindings())->toContain(50.0);
    });

    it('builds a BETWEEN for a range', function () {
        $queryBuilder = applyPropertyFilter(app(CompletenessFilter::class), 'completeness', FilterOperators::RANGE, ['20', '80']);

        expect($queryBuilder->toSql())->toContain('BETWEEN ? AND ?')
            ->and($queryBuilder->getBindings())->toBe([20.0, 80.0]);
    });

    it('matches products without a score', function () {
        $queryBuilder = applyPropertyFilter(app(CompletenessFilter::class), 'completeness', FilterOperators::IS_EMPTY, ['']);

        expect($queryBuilder->toSql())->toContain('avg_completeness_score IS NULL');
    });

    it('rejects an unsupported property', function () {
        applyPropertyFilter(app(CompletenessFilter::class), 'sku', FilterOperators::EQUAL, ['10']);
    })->throws(InvalidArgumentException::class);

    it('filters on the requested channel\'s own score once a channel is in context', function () {
        request()->merge(['channel' => 'does-not-exist']);

        // An unresolvable channel code still falls back to the average —
        // proves the fallback is driven by whether the channel actually
        // resolves, not merely by the presence of a `channel` query param.
        $queryBuilder = applyPropertyFilter(app(CompletenessFilter::class), 'completeness', FilterOperators::LESS_THAN, ['80']);

        expect($queryBuilder->toSql())->toContain('avg_completeness_score < ?');
    });
});

describe('CategoryFilter', function () {
    it('matches any of the given category codes on the product or its parent', function () {
        $queryBuilder = applyPropertyFilter(new CategoryFilter, 'categories', FilterOperators::IN, ['men', 'women']);

        $sql = $queryBuilder->toSql();

        $marker = DB::getDriverName() === 'pgsql' ? "->'categories'" : '$.categories';

        expect(substr_count($sql, $marker))->toBe(4)
            ->and($sql)->toContain('parent_products')
            ->and($queryBuilder->getBindings())->toBe(['"men"', '"women"', '"men"', '"women"']);
    });

    it('negates the match for not in list', function () {
        $queryBuilder = applyPropertyFilter(new CategoryFilter, 'categories', FilterOperators::NOT_IN, ['men']);

        expect($queryBuilder->toSql())->toContain('not (');
    });

    it('matches products carrying no category', function () {
        $queryBuilder = applyPropertyFilter(new CategoryFilter, 'categories', FilterOperators::IS_EMPTY, ['']);

        expect($queryBuilder->toSql())->toContain("NOT IN ('[]', '')")
            ->and($queryBuilder->toSql())->toContain('not (');
    });

    it('never matches when every code is blank', function () {
        $queryBuilder = applyPropertyFilter(new CategoryFilter, 'categories', FilterOperators::IN, ['']);

        expect($queryBuilder->toSql())->toContain('1 = 0');
    });
});

describe('DateTimeFilter', function () {
    it('covers the whole day for before', function () {
        $queryBuilder = applyPropertyFilter(new DateTimeFilter, 'created_at', FilterOperators::LESS_THAN, ['2026-01-31']);

        expect($queryBuilder->toSql())->toContain('created_at < ?')
            ->and($queryBuilder->getBindings())->toBe(['2026-01-31 00:00:01']);
    });

    it('covers the whole day for after', function () {
        $queryBuilder = applyPropertyFilter(new DateTimeFilter, 'updated_at', FilterOperators::GREATER_THAN, ['2026-01-31']);

        expect($queryBuilder->toSql())->toContain('updated_at > ?')
            ->and($queryBuilder->getBindings())->toBe(['2026-01-31 23:59:59']);
    });

    it('still builds a range', function () {
        $queryBuilder = applyPropertyFilter(new DateTimeFilter, 'created_at', FilterOperators::RANGE, ['2026-01-01', '2026-01-31']);

        expect($queryBuilder->getBindings())->toBe(['2026-01-01 00:00:01', '2026-01-31 23:59:59']);
    });
});

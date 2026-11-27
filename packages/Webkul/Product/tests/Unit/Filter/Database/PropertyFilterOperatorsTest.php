<?php

use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\Database\Property\CategoryFilter;
use Webkul\Product\Filter\Database\Property\CompletenessFilter;
use Webkul\Product\Filter\Database\Property\DateTimeFilter;

beforeEach(function (): void {
    config(['elasticsearch.enabled' => false]);
});

function applyPropertyFilter($filter, string $property, FilterOperators $operator, $value)
{
    $queryBuilder = DB::table('products');

    $filter->setQueryManager($queryBuilder);

    $filter->applyPropertyFilter($property, $operator, $value);

    return $queryBuilder;
}

describe('CompletenessFilter', function (): void {
    it('compares the average completeness score', function (): void {
        $queryBuilder = applyPropertyFilter(app(CompletenessFilter::class), 'completeness', FilterOperators::GREATER_THAN_OR_EQUAL, ['50']);

        expect($queryBuilder->toSql())->toContain(DB::getQueryGrammar()->wrap('products.avg_completeness_score').' >= ?')
            ->and($queryBuilder->getBindings())->toContain(50.0);
    });

    it('builds a BETWEEN for a range', function (): void {
        $queryBuilder = applyPropertyFilter(app(CompletenessFilter::class), 'completeness', FilterOperators::RANGE, ['20', '80']);

        expect($queryBuilder->toSql())->toContain('BETWEEN ? AND ?')
            ->and($queryBuilder->getBindings())->toBe([20.0, 80.0]);
    });

    it('matches products without a score', function (): void {
        $queryBuilder = applyPropertyFilter(app(CompletenessFilter::class), 'completeness', FilterOperators::IS_EMPTY, ['']);

        expect($queryBuilder->toSql())->toContain(DB::getQueryGrammar()->wrap('products.avg_completeness_score').' IS NULL');
    });

    it('rejects an unsupported property', function (): void {
        applyPropertyFilter(app(CompletenessFilter::class), 'sku', FilterOperators::EQUAL, ['10']);
    })->throws(InvalidArgumentException::class);

    it('falls back to the average when the requested channel code does not resolve', function (): void {
        request()->merge(['channel' => 'does-not-exist']);

        $queryBuilder = applyPropertyFilter(app(CompletenessFilter::class), 'completeness', FilterOperators::LESS_THAN, ['80']);

        expect($queryBuilder->toSql())->toContain(DB::getQueryGrammar()->wrap('products.avg_completeness_score').' < ?');
    });

    it('compares the requested channel\'s own score once a real channel is in context', function (): void {
        $channel = app(ChannelRepository::class)->create([
            'code'             => 'drilldown-scope',
            'root_category_id' => Category::whereIsRoot()->first()->id,
            'locales'          => Locale::where('status', 1)->limit(1)->pluck('id')->all(),
            'currencies'       => Currency::where('status', 1)->limit(1)->pluck('id')->all(),
        ])->load('locales');

        request()->merge(['channel' => $channel->code, 'locale' => $channel->locales->first()->code]);

        $queryBuilder = applyPropertyFilter(app(CompletenessFilter::class), 'completeness', FilterOperators::LESS_THAN, ['80']);

        expect($queryBuilder->toSql())
            ->toContain(DB::getQueryGrammar()->wrap('product_completeness.score').' < ?')
            ->and($queryBuilder->getBindings())->toContain(80.0);
    });
});

describe('CategoryFilter', function (): void {
    it('matches any of the given category codes on the product or its parent', function (): void {
        $queryBuilder = applyPropertyFilter(new CategoryFilter, 'categories', FilterOperators::IN, ['men', 'women']);

        $sql = $queryBuilder->toSql();

        $marker = DB::getDriverName() === 'pgsql' ? "->'categories'" : '$.categories';

        expect(substr_count($sql, $marker))->toBe(4)
            ->and($sql)->toContain('parent_products')
            ->and($queryBuilder->getBindings())->toBe(['"men"', '"women"', '"men"', '"women"']);
    });

    it('negates the match for not in list', function (): void {
        $queryBuilder = applyPropertyFilter(new CategoryFilter, 'categories', FilterOperators::NOT_IN, ['men']);

        expect($queryBuilder->toSql())->toContain('not (');
    });

    it('matches products carrying no category', function (): void {
        $queryBuilder = applyPropertyFilter(new CategoryFilter, 'categories', FilterOperators::IS_EMPTY, ['']);

        expect($queryBuilder->toSql())->toContain("NOT IN ('[]', '')")
            ->and($queryBuilder->toSql())->toContain('not (');
    });

    it('never matches when every code is blank', function (): void {
        $queryBuilder = applyPropertyFilter(new CategoryFilter, 'categories', FilterOperators::IN, ['']);

        expect($queryBuilder->toSql())->toContain('1 = 0');
    });
});

describe('DateTimeFilter', function (): void {
    it('covers the whole day for before', function (): void {
        $queryBuilder = applyPropertyFilter(new DateTimeFilter, 'created_at', FilterOperators::LESS_THAN, ['2026-01-31']);

        expect($queryBuilder->toSql())->toContain(DB::getQueryGrammar()->wrap('products.created_at').' < ?')
            ->and($queryBuilder->getBindings())->toBe(['2026-01-31 00:00:01']);
    });

    it('covers the whole day for after', function (): void {
        $queryBuilder = applyPropertyFilter(new DateTimeFilter, 'updated_at', FilterOperators::GREATER_THAN, ['2026-01-31']);

        expect($queryBuilder->toSql())->toContain(DB::getQueryGrammar()->wrap('products.updated_at').' > ?')
            ->and($queryBuilder->getBindings())->toBe(['2026-01-31 23:59:59']);
    });

    it('still builds a range', function (): void {
        $queryBuilder = applyPropertyFilter(new DateTimeFilter, 'created_at', FilterOperators::RANGE, ['2026-01-01', '2026-01-31']);

        expect($queryBuilder->getBindings())->toBe(['2026-01-01 00:00:01', '2026-01-31 23:59:59']);
    });
});

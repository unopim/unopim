<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\Database\DateFilter;
use Webkul\Product\Filter\Database\PriceFilter;
use Webkul\Product\Filter\Database\TextFilter;

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);
});

function conditionAttribute(string $code, string $type): Attribute
{
    $attribute = new Attribute;

    $attribute->code = $code;
    $attribute->type = $type;
    $attribute->value_per_locale = false;
    $attribute->value_per_channel = false;

    return $attribute;
}

function applyDatabaseFilter($filter, Attribute $attribute, FilterOperators $operator, $value)
{
    $queryBuilder = DB::table('products');

    $filter->setQueryManager($queryBuilder);

    $filter->addAttributeFilter($attribute, $operator, $value, 'en_US', 'default');

    return $queryBuilder;
}

describe('PriceFilter supports the operators offered by the product datagrid', function () {
    $price = fn () => conditionAttribute('price', Attribute::PRICE_FIELD_TYPE);

    it('compares numerically for greater than', function () use ($price) {
        $queryBuilder = applyDatabaseFilter(new PriceFilter, $price(), FilterOperators::GREATER_THAN, ['USD', '100']);

        expect($queryBuilder->toSql())->toContain('> ?')
            ->and($queryBuilder->toSql())->toContain('DECIMAL(8,2)')
            ->and($queryBuilder->getBindings())->toContain('100');
    });

    it('compares numerically for less than or equal', function () use ($price) {
        $queryBuilder = applyDatabaseFilter(new PriceFilter, $price(), FilterOperators::LESS_THAN_OR_EQUAL, ['USD', '27']);

        expect($queryBuilder->toSql())->toContain('<= ?')
            ->and($queryBuilder->getBindings())->toContain('27');
    });

    it('builds a BETWEEN for a range', function () use ($price) {
        $queryBuilder = applyDatabaseFilter(new PriceFilter, $price(), FilterOperators::RANGE, ['USD', '20', '50']);

        expect($queryBuilder->toSql())->toContain('BETWEEN ? AND ?')
            ->and($queryBuilder->getBindings())->toContain('20')
            ->and($queryBuilder->getBindings())->toContain('50');
    });

    it('treats a missing price as empty', function () use ($price) {
        $queryBuilder = applyDatabaseFilter(new PriceFilter, $price(), FilterOperators::IS_EMPTY, ['USD', '']);

        expect($queryBuilder->toSql())->toContain('COALESCE')
            ->and($queryBuilder->toSql())->toContain("= ''");
    });

    it('treats a present price as not empty', function () use ($price) {
        $queryBuilder = applyDatabaseFilter(new PriceFilter, $price(), FilterOperators::IS_NOT_EMPTY, ['USD', '']);

        expect($queryBuilder->toSql())->toContain('COALESCE')
            ->and($queryBuilder->toSql())->toContain("!= ''");
    });

    it('scopes the value to the chosen currency', function () use ($price) {
        $queryBuilder = applyDatabaseFilter(new PriceFilter, $price(), FilterOperators::GREATER_THAN, ['EUR', '10']);

        expect($queryBuilder->toSql())->toContain('EUR');
    });
});

describe('TextFilter supports the operators offered by the product datagrid', function () {
    $text = fn () => conditionAttribute('description', Attribute::TEXT_TYPE);

    it('matches case-insensitively for equals', function () use ($text) {
        $queryBuilder = applyDatabaseFilter(new TextFilter, $text(), FilterOperators::EQUAL, ['Shirt']);

        expect($queryBuilder->toSql())->toContain('LOWER')
            ->and($queryBuilder->getBindings())->toContain('shirt');
    });

    it('negates the match for not in list', function () use ($text) {
        $queryBuilder = applyDatabaseFilter(new TextFilter, $text(), FilterOperators::NOT_IN, ['Black']);

        expect($queryBuilder->toSql())->toContain('NOT')
            ->and($queryBuilder->getBindings())->toContain('Black');
    });

    it('treats a blank value as empty', function () use ($text) {
        $queryBuilder = applyDatabaseFilter(new TextFilter, $text(), FilterOperators::IS_EMPTY, ['']);

        expect($queryBuilder->toSql())->toContain('COALESCE')
            ->and($queryBuilder->toSql())->toContain("= ''");
    });

    it('keeps the existing contains behaviour', function () use ($text) {
        $queryBuilder = applyDatabaseFilter(new TextFilter, $text(), FilterOperators::CONTAINS, ['nike']);

        expect($queryBuilder->toSql())->toContain('LIKE')
            ->and($queryBuilder->getBindings())->toContain('%nike%');
    });
});

describe('DateFilter supports before and after', function () {
    $date = fn () => conditionAttribute('launch_date', Attribute::DATE_FIELD_TYPE);

    it('uses the start of the day for before', function () use ($date) {
        $queryBuilder = applyDatabaseFilter(new DateFilter, $date(), FilterOperators::LESS_THAN, ['2026-01-01']);

        expect($queryBuilder->toSql())->toContain('< ?')
            ->and($queryBuilder->getBindings())->toContain('2026-01-01 00:00:01');
    });

    it('uses the end of the day for after', function () use ($date) {
        $queryBuilder = applyDatabaseFilter(new DateFilter, $date(), FilterOperators::GREATER_THAN, ['2026-01-01']);

        expect($queryBuilder->toSql())->toContain('> ?')
            ->and($queryBuilder->getBindings())->toContain('2026-01-01 23:59:59');
    });

    it('keeps the existing range behaviour', function () use ($date) {
        $queryBuilder = applyDatabaseFilter(new DateFilter, $date(), FilterOperators::RANGE, ['2026-01-01', '2026-02-01']);

        expect($queryBuilder->toSql())->toContain('BETWEEN ? AND ?')
            ->and($queryBuilder->getBindings())->toContain('2026-01-01 00:00:01');
    });
});

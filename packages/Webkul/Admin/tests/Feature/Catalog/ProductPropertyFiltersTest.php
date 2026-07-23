<?php

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Filters\ProductPropertyFilters;
use Webkul\ElasticSearch\Enums\FilterOperators;

function productPropertyColumns(): array
{
    return app(ProductDataGrid::class)->getPropertyColumns();
}

describe('opt-in product property filters', function () {

    it('offers dates, completeness and categories to the filter picker', function () {
        expect(array_column(ProductPropertyFilters::pickerOptions(), 'index'))->toBe([
            ProductPropertyFilters::CREATED_AT,
            ProductPropertyFilters::UPDATED_AT,
            ProductPropertyFilters::COMPLETENESS,
            ProductPropertyFilters::CATEGORIES,
        ]);
    });

    it('never renders picker entries as grid columns', function () {
        foreach (ProductPropertyFilters::pickerOptions() as $column) {
            expect($column['visible'])->toBeFalse()
                ->and($column['filterable'])->toBeTrue()
                ->and($column['default_filter'])->toBeFalse();
        }
    });

    it('matches the picker search against label and index', function () {
        expect(array_column(ProductPropertyFilters::pickerOptions('categor'), 'index'))
            ->toBe([ProductPropertyFilters::CATEGORIES])
            ->and(array_column(ProductPropertyFilters::pickerOptions('updated_at'), 'index'))
            ->toBe([ProductPropertyFilters::UPDATED_AT])
            ->and(ProductPropertyFilters::pickerOptions('no-such-filter'))->toBeEmpty();
    });

    it('offers list membership for categories and numeric comparisons for completeness', function () {
        $operators = fn (string $index): array => array_column(
            ProductPropertyFilters::get($index)['operators'],
            'value'
        );

        expect($operators(ProductPropertyFilters::CATEGORIES))
            ->toContain(FilterOperators::IN->value)
            ->toContain(FilterOperators::NOT_IN->value)
            ->and($operators(ProductPropertyFilters::COMPLETENESS))
            ->toContain(FilterOperators::GREATER_THAN_OR_EQUAL->value)
            ->toContain(FilterOperators::RANGE->value);
    });
});

describe('the product datagrid filter drawer', function () {

    it('spells out the completeness filter while the column header stays abbreviated', function () {
        $completeness = ProductPropertyFilters::get(ProductPropertyFilters::COMPLETENESS);

        expect($completeness['filter_label'])->toBe(trans('completeness::app.components.layouts.sidebar.completeness'))
            ->and($completeness['label'])->not->toBe($completeness['filter_label']);
    });

    it('pins the sku filter but lets family, status and type be removed', function () {
        $columns = productPropertyColumns();

        expect($columns['sku']['removable_filter'] ?? false)->toBeFalse();

        foreach (['attribute_family', 'status', 'type'] as $index) {
            expect($columns[$index]['removable_filter'])->toBeTrue();
        }
    });

    it('registers a requested property filter as a hidden column', function () {
        request()->merge(['filters' => ['created_at' => [['operator' => 'gt', 'value' => '2026-01-01']]]]);

        $datagrid = app(ProductDataGrid::class);
        $datagrid->setQueryBuilder();
        $datagrid->prepareColumns();

        $columns = collect($datagrid->getColumns());

        $createdAt = $columns->firstWhere('index', 'created_at');

        expect($createdAt)->not->toBeNull()
            ->and($createdAt->visible)->toBeFalse()
            ->and($createdAt->filterable)->toBeTrue();

        /** Grid columns already carrying filter metadata are never duplicated. */
        expect($columns->where('index', 'completeness'))->toHaveCount(1)
            ->and($columns->firstWhere('index', 'completeness')->filterable)->toBeTrue()
            ->and($columns->firstWhere('index', 'completeness')->default_filter)->toBeFalse();

        request()->replace([]);
    });
});

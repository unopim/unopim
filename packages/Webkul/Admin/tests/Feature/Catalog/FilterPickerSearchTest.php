<?php

use Webkul\Admin\Filters\ProductPropertyFilters;

function pickerLabels(string $search): array
{
    return array_map(
        fn (array $column): string => (string) $column['label'],
        ProductPropertyFilters::pickerOptions($search)
    );
}

it('finds a plural filter from its singular form', function () {
    expect(pickerLabels('category'))->toContain('Categories');
});

it('finds a filter from a prefix', function () {
    expect(pickerLabels('categ'))->toContain('Categories');
});

it('finds a filter from its exact label', function () {
    expect(pickerLabels('Categories'))->toContain('Categories');
});

it('finds a filter from its index when the label differs', function () {
    expect(pickerLabels('completeness'))->toContain('Complete');
});

it('returns every filter for an empty search', function () {
    expect(pickerLabels(''))->toContain('Categories', 'Complete');
});

it('returns nothing for a term no filter carries', function () {
    expect(pickerLabels('supplier'))->toBeEmpty();
});

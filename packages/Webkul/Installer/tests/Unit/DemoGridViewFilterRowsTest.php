<?php

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;

/**
 * `applyView()` replaces the active filter list wholesale with the payload's,
 * so a view that names only its own index hides every default filter row from
 * the drawer. A view saved through the UI never looks like that: it snapshots
 * the live list, which already carries the grid's defaults.
 */
function productGridDefaultFilters(): array
{
    $property = new ReflectionProperty(ProductDataGrid::class, 'defaultFilters');

    return $property->getValue(resolve(ProductDataGrid::class));
}

function demoGridViews(): array
{
    return (require __DIR__.'/../../src/Database/Data/Demo/grid_views.php')['views'];
}

it('keeps the grid default filter rows on every seeded view', function () {
    $defaults = productGridDefaultFilters();

    expect($defaults)->not->toBeEmpty();

    $missing = [];

    foreach (demoGridViews() as $view) {
        $active = $view['payload']['activeFilterIndices'] ?? [];

        foreach ($defaults as $index) {
            if (! in_array($index, $active, true)) {
                $missing[] = "{$view['name']} drops the $index row";
            }
        }
    }

    expect($missing)->toBe([]);
});

it('states an operator on every filter so the drawer can show its value', function () {
    /**
     * `syncAttributeConditions()` only reads a value back out of an applied
     * filter when the first entry is a {operator, value} object. A bare scalar
     * leaves the control empty while the operator falls back to the first one
     * the column allows — the filter reads as set but shows nothing.
     */
    $bare = [];

    foreach (demoGridViews() as $view) {
        foreach ($view['payload']['filters'] ?? [] as $filter) {
            $first = is_array($filter['value']) ? ($filter['value'][0] ?? null) : $filter['value'];

            if (! is_array($first) || ! isset($first['operator'])) {
                $bare[] = "{$view['name']} states no operator for {$filter['index']}";
            }
        }
    }

    expect($bare)->toBe([]);
});

it('gives every filtered index a row of its own', function () {
    $missing = [];

    foreach (demoGridViews() as $view) {
        $active = $view['payload']['activeFilterIndices'] ?? [];

        foreach ($view['payload']['filters'] ?? [] as $filter) {
            if (! in_array($filter['index'], $active, true)) {
                $missing[] = "{$view['name']} filters on {$filter['index']} without a row";
            }
        }
    }

    expect($missing)->toBe([]);
});

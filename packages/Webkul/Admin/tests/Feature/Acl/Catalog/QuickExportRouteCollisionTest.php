<?php

use Webkul\Core\Tree;

$aclTree = function (?callable $mutate = null): Tree {
    $items = collect(config('acl'))->map(fn (array $item): array => $mutate ? $mutate($item) : $item)->all();

    $tree = new Tree;

    foreach ($items as $item) {
        $tree->add($item, 'acl');
    }

    return $tree;
};

$claimListingRoute = fn (array $item): array => $item['key'] === 'catalog.products.quick_export'
    ? [...$item, 'route' => 'admin.catalog.products.index']
    : $item;

it('gives quick export a route of its own', function () use ($aclTree) {
    expect($aclTree()->roles['admin.catalog.products.quick-export'])->toBe('catalog.products.quick_export');
});

it('leaves the product listing route owned by the view permission', function () use ($aclTree) {
    expect($aclTree()->roles['admin.catalog.products.index'])->toBe('catalog.products');
});

it('lets a view only role open the product listing', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'catalog', 'catalog.products']);

    $this->get(route('admin.catalog.products.index'))->assertOk();
});

it('hands the listing route to quick export if that route is named on the node', function () use ($aclTree, $claimListingRoute) {
    expect($aclTree($claimListingRoute)->roles['admin.catalog.products.index'])->toBe('catalog.products.quick_export');
});

it('locks a view only role out of the listing once quick export claims that route', function () use ($aclTree, $claimListingRoute) {
    app()->instance('acl', $aclTree($claimListingRoute));

    $this->loginWithPermissions(permissions: ['dashboard', 'catalog', 'catalog.products']);

    $this->get(route('admin.catalog.products.index'))->assertStatus(403);
});

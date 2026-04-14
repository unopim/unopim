<?php

beforeEach(function () {
    $this->loginAsAdmin();
});

it('renders the dashboard product-stats widget without errors', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        // The new clickable filter chips depend on this Vue helper.
        ->assertSee('productsUrl', false);
});

it('renders the Active stat as a status=true filter link', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee("productsUrl({ status: 'true' })", false);
});

it('renders the Inactive stat as a status=false filter link', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        ->assertSee("productsUrl({ status: 'false' })", false);
});

it('renders the type-distribution legend chips as type filter links', function () {
    $this->get(route('admin.dashboard.index'))
        ->assertOk()
        // The legend loop binds :href="productsUrl({ type })" — present in the
        // template even when the user has zero products of that type because
        // the loop iterates the typeDistribution payload from the AJAX call.
        ->assertSee('productsUrl({ type })', false);
});

it('returns the product-stats payload from the dashboard stats endpoint', function () {
    $response = $this->getJson(route('admin.dashboard.stats', ['type' => 'product-stats']));

    $response->assertOk();

    // The widget consumes these keys; refactoring the helper must not
    // change the contract the Vue component depends on.
    $response->assertJsonStructure([
        'statistics' => [
            'totalProducts',
            'statusBreakdown',
            'typeDistribution',
        ],
    ]);
});

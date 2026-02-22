<?php

use Webkul\Pricing\Models\ProductCost;
use Webkul\Pricing\Models\PricingStrategy;
use Webkul\Pricing\Models\MarginProtectionEvent;
use Webkul\Product\Models\Product;

// Product Costs ACL Tests
it('should not display costs list without permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.pricing.costs.index'))
        ->assertSeeText('Unauthorized');
});

it('should display costs list with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.costs']);

    $this->get(route('admin.pricing.costs.index'))
        ->assertOk()
        ->assertSeeText(trans('pricing::app.costs.index.title'));
});

it('should not display cost create form without permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.costs']);

    $this->get(route('admin.pricing.costs.create'))
        ->assertSeeText('Unauthorized');
});

it('should display cost create form with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.costs', 'pricing.costs.create']);

    $this->get(route('admin.pricing.costs.create'))
        ->assertOk()
        ->assertSeeText(trans('pricing::app.costs.create.title'));
});

it('should not be able to delete cost without permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.costs']);
    $cost = ProductCost::factory()->create();

    $this->delete(route('admin.pricing.costs.delete', $cost->id))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(ProductCost::class), ['id' => $cost->id]);
});

it('should be able to delete cost with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.costs', 'pricing.costs.delete']);
    $cost = ProductCost::factory()->create();

    $this->delete(route('admin.pricing.costs.delete', $cost->id))
        ->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(ProductCost::class), ['id' => $cost->id]);
});

// Channel Costs ACL Tests
it('should not display channel costs list without permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.pricing.channel-costs.index'))
        ->assertSeeText('Unauthorized');
});

it('should display channel costs list with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.channel-costs']);

    $this->get(route('admin.pricing.channel-costs.index'))
        ->assertOk()
        ->assertSeeText(trans('pricing::app.channel-costs.index.title'));
});

// Break-Even Calculator ACL Tests
it('should not display break-even calculator without permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.pricing.break-even.show'))
        ->assertSeeText('Unauthorized');
});

it('should display break-even calculator with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.break-even']);

    $this->get(route('admin.pricing.break-even.show'))
        ->assertOk()
        ->assertSeeText(trans('pricing::app.break-even.show.title'));
});

// Recommendations ACL Tests
it('should not display recommendations without permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.pricing.recommendations.show'))
        ->assertSeeText('Unauthorized');
});

it('should display recommendations with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.recommendations']);

    $this->get(route('admin.pricing.recommendations.show'))
        ->assertOk()
        ->assertSeeText(trans('pricing::app.recommendations.show.title'));
});

// Margin Protection ACL Tests
it('should not display margin protection events without permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.pricing.margins.index'))
        ->assertSeeText('Unauthorized');
});

it('should display margin protection events with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.margins']);

    $this->get(route('admin.pricing.margins.index'))
        ->assertOk()
        ->assertSeeText(trans('pricing::app.margins.index.title'));
});

it('should not approve margin event without permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.margins']);
    $event = MarginProtectionEvent::factory()->create(['event_type' => 'blocked']);

    $this->post(route('admin.pricing.margins.approve', $event->id))
        ->assertSeeText('Unauthorized');
});

it('should approve margin event with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.margins', 'pricing.margins.approve']);
    $event = MarginProtectionEvent::factory()->create(['event_type' => 'blocked']);

    $this->post(route('admin.pricing.margins.approve', $event->id))
        ->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(MarginProtectionEvent::class), [
        'id' => $event->id,
        'event_type' => 'approved',
    ]);
});

// Pricing Strategies ACL Tests
it('should not display strategies list without permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.pricing.strategies.index'))
        ->assertSeeText('Unauthorized');
});

it('should display strategies list with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.strategies']);

    $this->get(route('admin.pricing.strategies.index'))
        ->assertOk()
        ->assertSeeText(trans('pricing::app.strategies.index.title'));
});

it('should not create strategy without permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.strategies']);

    $this->get(route('admin.pricing.strategies.create'))
        ->assertSeeText('Unauthorized');
});

it('should create strategy with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.strategies', 'pricing.strategies.create']);

    $this->get(route('admin.pricing.strategies.create'))
        ->assertOk()
        ->assertSeeText(trans('pricing::app.strategies.create.title'));
});

it('should not delete strategy without permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.strategies']);
    $strategy = PricingStrategy::factory()->create();

    $this->delete(route('admin.pricing.strategies.delete', $strategy->id))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(PricingStrategy::class), ['id' => $strategy->id]);
});

it('should delete strategy with permission', function () {
    $this->loginWithPermissions(permissions: ['pricing', 'pricing.strategies', 'pricing.strategies.delete']);
    $strategy = PricingStrategy::factory()->create();

    $this->delete(route('admin.pricing.strategies.delete', $strategy->id))
        ->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(PricingStrategy::class), ['id' => $strategy->id]);
});

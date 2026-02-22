<?php

use Webkul\Pricing\Models\PricingStrategy;
use Webkul\Core\Models\Channel;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\Product;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;

it('should return the strategies index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.pricing.strategies.index'));

    $response->assertStatus(200)
        ->assertSeeText(trans('pricing::app.strategies.index.title'));
});

it('should create a global pricing strategy', function () {
    $this->loginAsAdmin();

    $strategyData = [
        'scope_type' => 'global',
        'scope_id' => 0,
        'minimum_margin_percentage' => 20.00,
        'target_margin_percentage' => 30.00,
        'premium_margin_percentage' => 40.00,
        'psychological_pricing' => true,
        'round_to' => '0.99',
        'is_active' => true,
        'priority' => 100,
    ];

    $response = postJson(route('admin.pricing.strategies.store'), $strategyData);

    $this->assertDatabaseHas($this->getFullTableName(PricingStrategy::class), [
        'scope_type' => 'global',
        'scope_id' => 0,
        'minimum_margin_percentage' => 20.00,
    ]);

    $response->assertStatus(302);
});

it('should create a channel-specific pricing strategy', function () {
    $this->loginAsAdmin();
    $channel = Channel::factory()->create();

    $strategyData = [
        'scope_type' => 'channel',
        'scope_id' => $channel->id,
        'minimum_margin_percentage' => 15.00,
        'target_margin_percentage' => 25.00,
        'premium_margin_percentage' => 35.00,
        'psychological_pricing' => false,
        'round_to' => 'none',
        'is_active' => true,
        'priority' => 50,
    ];

    $response = postJson(route('admin.pricing.strategies.store'), $strategyData);

    $this->assertDatabaseHas($this->getFullTableName(PricingStrategy::class), [
        'scope_type' => 'channel',
        'scope_id' => $channel->id,
    ]);

    $response->assertStatus(302);
});

it('should validate margin percentage ordering', function () {
    $this->loginAsAdmin();

    $invalidData = [
        'scope_type' => 'global',
        'scope_id' => 0,
        'minimum_margin_percentage' => 40.00,
        'target_margin_percentage' => 30.00,
        'premium_margin_percentage' => 20.00,
        'is_active' => true,
        'priority' => 100,
    ];

    $response = postJson(route('admin.pricing.strategies.store'), $invalidData);

    $response->assertStatus(422);
});

it('should update a pricing strategy', function () {
    $this->loginAsAdmin();

    $strategy = PricingStrategy::factory()->create([
        'minimum_margin_percentage' => 20.00,
    ]);

    $updateData = [
        'minimum_margin_percentage' => 25.00,
        'target_margin_percentage' => 35.00,
        'premium_margin_percentage' => 45.00,
        'priority' => 200,
    ];

    $response = putJson(route('admin.pricing.strategies.update', $strategy->id), $updateData);

    $this->assertDatabaseHas($this->getFullTableName(PricingStrategy::class), [
        'id' => $strategy->id,
        'minimum_margin_percentage' => 25.00,
    ]);

    $response->assertStatus(302);
});

it('should delete a pricing strategy', function () {
    $this->loginAsAdmin();

    $strategy = PricingStrategy::factory()->create();

    $response = deleteJson(route('admin.pricing.strategies.delete', $strategy->id));

    $this->assertDatabaseMissing($this->getFullTableName(PricingStrategy::class), [
        'id' => $strategy->id,
    ]);

    $response->assertStatus(200);
});

it('should enforce unique constraint on scope_type + scope_id', function () {
    $this->loginAsAdmin();

    PricingStrategy::factory()->create([
        'scope_type' => 'global',
        'scope_id' => 0,
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    PricingStrategy::factory()->create([
        'scope_type' => 'global',
        'scope_id' => 0,
    ]);
});

it('should retrieve strategies for a product with cascading priority', function () {
    $this->loginAsAdmin();
    $product = Product::factory()->create();
    $channel = Channel::factory()->create();

    PricingStrategy::factory()->create([
        'scope_type' => 'global',
        'scope_id' => 0,
        'minimum_margin_percentage' => 20.00,
        'is_active' => true,
        'priority' => 100,
    ]);

    PricingStrategy::factory()->create([
        'scope_type' => 'product',
        'scope_id' => $product->id,
        'minimum_margin_percentage' => 15.00,
        'is_active' => true,
        'priority' => 10,
    ]);

    $repo = app(\Webkul\Pricing\Contracts\PricingStrategyRepository::class);
    $strategy = $repo->resolve($product->id, $channel->id, null);

    expect($strategy)->not->toBeNull()
        ->and($strategy->minimum_margin_percentage)->toBe(15.00);
});

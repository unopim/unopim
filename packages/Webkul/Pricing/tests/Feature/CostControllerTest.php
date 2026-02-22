<?php

use Webkul\Pricing\Models\ProductCost;
use Webkul\Product\Models\Product;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;

it('should return the costs index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.pricing.costs.index'));

    $response->assertStatus(200)
        ->assertSeeText(trans('pricing::app.costs.index.title'));
});

it('should return the costs datagrid', function () {
    $this->loginAsAdmin();

    $cost = ProductCost::factory()->create();

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.pricing.costs.index'));

    $response->assertStatus(200);

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(ProductCost::class), [
        'id' => $data['records'][0]['id'],
    ]);
});

it('should show the create cost form', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.pricing.costs.create'));

    $response->assertStatus(200)
        ->assertSeeText(trans('pricing::app.costs.create.title'));
});

it('should create a product cost', function () {
    $this->loginAsAdmin();
    $product = Product::factory()->create();

    $costData = [
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 125.50,
        'currency_code' => 'USD',
        'effective_from' => now()->format('Y-m-d'),
    ];

    $response = postJson(route('admin.pricing.costs.store'), $costData);

    $this->assertDatabaseHas($this->getFullTableName(ProductCost::class), [
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'amount' => 125.50,
        'currency_code' => 'USD',
    ]);

    $response->assertStatus(302);
});

it('should validate required fields when creating cost', function () {
    $this->loginAsAdmin();

    $response = postJson(route('admin.pricing.costs.store'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['product_id', 'cost_type', 'amount', 'currency_code', 'effective_from']);
});

it('should show the edit cost form', function () {
    $this->loginAsAdmin();

    $cost = ProductCost::factory()->create();

    $response = get(route('admin.pricing.costs.edit', $cost->id));

    $response->assertStatus(200)
        ->assertSeeText(trans('pricing::app.costs.edit.title'));
});

it('should update a product cost', function () {
    $this->loginAsAdmin();

    $cost = ProductCost::factory()->create([
        'amount' => 100.00,
    ]);

    $updateData = [
        'amount' => 150.00,
        'currency_code' => $cost->currency_code,
        'effective_to' => now()->addMonth()->format('Y-m-d'),
    ];

    $response = putJson(route('admin.pricing.costs.update', $cost->id), $updateData);

    $this->assertDatabaseHas($this->getFullTableName(ProductCost::class), [
        'id' => $cost->id,
        'amount' => 150.00,
    ]);

    $response->assertStatus(302);
});

it('should delete a product cost', function () {
    $this->loginAsAdmin();

    $cost = ProductCost::factory()->create();

    $response = deleteJson(route('admin.pricing.costs.delete', $cost->id));

    $this->assertDatabaseMissing($this->getFullTableName(ProductCost::class), [
        'id' => $cost->id,
    ]);

    $response->assertStatus(200);
});

it('should mass delete product costs', function () {
    $this->loginAsAdmin();

    $costs = ProductCost::factory()->count(3)->create();
    $ids = $costs->pluck('id')->toArray();

    $response = postJson(route('admin.pricing.costs.mass_delete'), [
        'indices' => $ids,
    ]);

    foreach ($ids as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(ProductCost::class), ['id' => $id]);
    }

    $response->assertStatus(200);
});

it('should return costs for a specific product', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create();
    ProductCost::factory()->count(3)->create(['product_id' => $product->id]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.pricing.costs.for_product', $product->id));

    $response->assertStatus(200);

    $data = $response->json();

    expect($data)->toHaveKey('breakdown')
        ->and(count($data['breakdown']))->toBeGreaterThan(0);
});

it('should enforce unique constraint on product_id + cost_type + effective_from', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create();
    $effectiveFrom = now()->format('Y-m-d');

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'effective_from' => $effectiveFrom,
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    ProductCost::factory()->create([
        'product_id' => $product->id,
        'cost_type' => 'cogs',
        'effective_from' => $effectiveFrom,
    ]);
});

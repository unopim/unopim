<?php

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

/*
 * Delta-sync filters (updated_at / created_at) and search_after cursor
 * pagination on the product listing API.
 */

function createProductStampedAt(string $sku, string $updatedAt, ?string $createdAt = null): Product
{
    $product = Product::factory()->simple()->create(['sku' => $sku]);

    DB::table('products')->where('id', $product->id)->update([
        'updated_at' => $updatedAt,
        'created_at' => $createdAt ?? $updatedAt,
    ]);

    return $product;
}

it('returns only products updated after the given date', function () {
    $headers = $this->getAuthenticationHeaders();

    createProductStampedAt('DELTA-OLD', '2030-01-01 00:00:00');
    createProductStampedAt('DELTA-NEW', '2030-03-01 00:00:00');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode(['updated_at' => [['operator' => '>', 'value' => '2030-02-01 00:00:00']]]),
        'limit'   => 100,
    ]));

    $response->assertOk();

    $skus = array_column($response->json('data'), 'sku');

    expect($skus)->toContain('DELTA-NEW')
        ->not->toContain('DELTA-OLD');
});

it('supports BETWEEN on updated_at', function () {
    $headers = $this->getAuthenticationHeaders();

    createProductStampedAt('BETWEEN-BEFORE', '2030-01-01 00:00:00');
    createProductStampedAt('BETWEEN-IN', '2030-02-15 00:00:00');
    createProductStampedAt('BETWEEN-AFTER', '2030-04-01 00:00:00');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode(['updated_at' => [[
            'operator' => 'BETWEEN',
            'value'    => ['2030-02-01 00:00:00', '2030-03-01 00:00:00'],
        ]]]),
        'limit' => 100,
    ]));

    $response->assertOk();

    $skus = array_column($response->json('data'), 'sku');

    expect($skus)->toContain('BETWEEN-IN')
        ->not->toContain('BETWEEN-BEFORE')
        ->not->toContain('BETWEEN-AFTER');
});

it('filters by created_at independently of updated_at', function () {
    $headers = $this->getAuthenticationHeaders();

    createProductStampedAt('CREATED-OLD', '2030-05-01 00:00:00', '2030-01-01 00:00:00');
    createProductStampedAt('CREATED-NEW', '2030-05-01 00:00:00', '2030-04-01 00:00:00');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode(['created_at' => [['operator' => '>=', 'value' => '2030-03-01 00:00:00']]]),
        'limit'   => 100,
    ]));

    $response->assertOk();

    $skus = array_column($response->json('data'), 'sku');

    expect($skus)->toContain('CREATED-NEW')
        ->not->toContain('CREATED-OLD');
});

it('rejects an unparseable date value with 422', function () {
    $headers = $this->getAuthenticationHeaders();

    $this->withHeaders($headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode(['updated_at' => [['operator' => '>', 'value' => 'not-a-date']]]),
    ]))->assertUnprocessable();
});

it('rejects an unsupported operator on updated_at with 422', function () {
    $headers = $this->getAuthenticationHeaders();

    $this->withHeaders($headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode(['updated_at' => [['operator' => 'IN', 'value' => ['2030-01-01']]]]),
    ]))->assertUnprocessable();
});

it('rejects BETWEEN without exactly two values with 422', function () {
    $headers = $this->getAuthenticationHeaders();

    $this->withHeaders($headers)->json('GET', route('admin.api.products.index', [
        'filters' => json_encode(['updated_at' => [['operator' => 'BETWEEN', 'value' => ['2030-01-01']]]]),
    ]))->assertUnprocessable();
});

it('walks the full result set through search_after cursor pages without overlap', function () {
    $headers = $this->getAuthenticationHeaders();

    foreach (range(1, 5) as $i) {
        createProductStampedAt("CURSOR-{$i}", '2031-01-0'.$i.' 00:00:00');
    }

    $filters = json_encode(['updated_at' => [['operator' => '>', 'value' => '2030-12-01 00:00:00']]]);

    $collected = [];
    $searchAfter = null;
    $pages = 0;

    do {
        $params = [
            'filters'         => $filters,
            'limit'           => 2,
            'pagination_type' => 'search_after',
        ];

        if ($searchAfter !== null) {
            $params['search_after'] = $searchAfter;
        }

        $response = $this->withHeaders($headers)->json('GET', route('admin.api.products.index', $params));

        $response->assertOk();

        $body = $response->json();

        expect($body)->not->toHaveKey('total');

        $collected = array_merge($collected, array_column($body['data'], 'sku'));
        $searchAfter = $body['search_after'];
        $pages++;
    } while ($searchAfter !== null && $pages < 10);

    expect($collected)->toHaveCount(5)
        ->and(array_unique($collected))->toHaveCount(5)
        ->and($pages)->toBe(3);
});

it('keeps the default page-based response shape unchanged', function () {
    $headers = $this->getAuthenticationHeaders();

    createProductStampedAt('SHAPE-1', '2030-01-01 00:00:00');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.products.index', ['limit' => 10]));

    $response->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'last_page', 'total', 'links' => ['first', 'last', 'next', 'prev']]);
});

it('rejects a non-numeric search_after cursor with 422', function () {
    $headers = $this->getAuthenticationHeaders();

    $this->withHeaders($headers)->json('GET', route('admin.api.products.index', [
        'pagination_type' => 'search_after',
        'search_after'    => 'abc',
    ]))->assertUnprocessable();
});

it('rejects an unknown pagination_type with 422', function () {
    $headers = $this->getAuthenticationHeaders();

    $this->withHeaders($headers)->json('GET', route('admin.api.products.index', [
        'pagination_type' => 'cursor',
    ]))->assertUnprocessable();
});

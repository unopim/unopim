<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\AiAgent\Chat\ReplyGuard;
use Webkul\Product\Models\Product;

function replyGuardEditLink(int $id, string $label = 'Product'): string
{
    return "[{$label}](".route('admin.catalog.products.edit', ['id' => $id]).')';
}

it('keeps replies whose numbers, years, prices and codes look like catalog SKUs', function (string $sku, string $reply) {
    Product::factory()->simple()->create(['sku' => $sku]);

    expect(app(ReplyGuard::class)->guardProductGrounding($reply, ['search_products']))->toBe($reply)
        ->and(app(ReplyGuard::class)->guardProductGrounding($reply, []))->toBe($reply);
})->with([
    'numeric SKUs' => ['1001', 'In 2026 we sold 4512 units of SKU 1001 at 1999 each, order 7731 ships today.'],
    'dashed SKUs'  => ['AB-12', 'AB-12 replaces CD-34 from the supplier sheet; also see ZZ-99 in the history.'],
    'proposal'     => ['NEW-1', "I will create:\n- SKU: NEW-2\n- Price: 49.95\nShall I proceed? (yes/no)"],
]);

it('replaces a reply that links a product that does not exist', function () {
    $product = Product::factory()->simple()->create();
    $missing = (int) DB::table('products')->max('id') + 100;

    $reply = 'Found: '.replyGuardEditLink($product->id).' and '.replyGuardEditLink($missing, 'Ghost');

    expect(app(ReplyGuard::class)->guardProductGrounding($reply, ['search_products']))
        ->toBe(trans('ai-agent::app.common.ungrounded-product-reply'));
});

it('resolves every linked product with a single query', function () {
    $products = Product::factory()->simple()->count(3)->create();
    $reply = $products->map(fn (Product $product) => replyGuardEditLink($product->id))->implode("\n");

    DB::enableQueryLog();
    $result = app(ReplyGuard::class)->guardProductGrounding($reply, ['search_products']);
    $queries = collect(DB::getQueryLog())->filter(fn (array $query) => str_contains($query['query'], 'products'));
    DB::disableQueryLog();

    expect($result)->toBe($reply)
        ->and($queries)->toHaveCount(1);
});

it('replaces a product presented without any tool call unless the history already contains it', function () {
    $product = Product::factory()->simple()->create();
    $reply = 'Here it is again: '.replyGuardEditLink($product->id);
    $guard = app(ReplyGuard::class);

    expect($guard->guardProductGrounding($reply, []))->toBe(trans('ai-agent::app.common.ungrounded-product-reply'))
        ->and($guard->guardProductGrounding($reply, [], [['role' => 'assistant', 'content' => 'Earlier: '.replyGuardEditLink($product->id)]]))->toBe($reply)
        ->and($guard->guardProductGrounding($reply, null))->toBe($reply);
});

it('matches edit links through the named product edit route', function () {
    $guard = app(ReplyGuard::class);

    expect($guard->linkedProductIds(replyGuardEditLink(12).' '.replyGuardEditLink(7).' '.replyGuardEditLink(12)))->toBe([12, 7])
        ->and($guard->linkedProductIds('[Old style](/admin/catalog/products/12/edit) and /catalog/products/edit/'))->toBe([]);
});

it('replaces only chat images whose stored file is missing', function () {
    Storage::fake('public');
    Storage::disk('public')->put('product/1/real.png', 'png');

    $reply = "![Real](/storage/product/1/real.png)\n![Gone](/storage/product/1/gone.png)\n![](/storage/missing.png)\n![Remote](https://cdn.example.test/x.png)";

    expect(app(ReplyGuard::class)->stripUnresolvableImages($reply))->toBe(implode("\n", [
        '![Real](/storage/product/1/real.png)',
        trans('ai-agent::app.common.image-unavailable-with-alt', ['alt' => 'Gone']),
        trans('ai-agent::app.common.image-unavailable'),
        '![Remote](https://cdn.example.test/x.png)',
    ]));
});

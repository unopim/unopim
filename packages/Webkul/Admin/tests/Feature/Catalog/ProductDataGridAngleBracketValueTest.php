<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    $this->loginAsAdmin();

    $this->nameCode = 'nm_'.Str::random(8);
    $this->weightCode = 'wt_'.Str::random(8);

    $attributes = collect([$this->nameCode, $this->weightCode])->map(fn (string $code) => Attribute::factory()->create([
        'code'              => $code,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]));

    $this->family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($this->family);
    AttributeFamily::factory()->linkAttributesToFamily($this->family, $attributes);
});

function createSimpleProductWithValues(AttributeFamily $family, array $commonValues): Product
{
    $product = app(ProductRepository::class)->create([
        'type'                => ProductType::Simple->value,
        'attribute_family_id' => $family->id,
        'sku'                 => 'SMP-'.Str::random(8),
    ]);

    $product->values = ['common' => ['sku' => $product->sku, ...$commonValues]];
    $product->save();

    return $product->fresh();
}

function productGridRecord(int $productId, array $columns): ?array
{
    request()->replace([
        'managedColumns' => ['product_id', 'sku', ...$columns],
        'productIds'     => [$productId],
    ]);

    $grid = app(ProductDataGrid::class);

    $grid->prepare();

    $records = json_decode(json_encode($grid->formatData()['records']), true);

    return collect($records)->firstWhere('product_id', $productId);
}

it('shows a simple product value that contains a less-than sign directly before a character', function (string $name) {
    $product = createSimpleProductWithValues($this->family, [
        $this->nameCode   => $name,
        $this->weightCode => '2.5',
    ]);

    $record = productGridRecord($product->id, [$this->nameCode, $this->weightCode]);

    expect($record)->not->toBeNull()
        ->and($record[$this->nameCode])->toBe($name)
        ->and($record[$this->weightCode])->toBe('2.5');
})->with([
    'digit after <'  => 'Washer <5mm thick',
    'number after <' => 'Bolt <10',
    'letter after <' => 'Rated <abc grade',
    'space after <'  => 'a < b',
    'comparison'     => 'x <= 5 and y >= 2',
]);

it('still removes real HTML tags from a simple product value', function (string $stored, string $shown) {
    $product = createSimpleProductWithValues($this->family, [
        $this->nameCode   => $stored,
        $this->weightCode => '3',
    ]);

    $record = productGridRecord($product->id, [$this->nameCode, $this->weightCode]);

    expect($record)->not->toBeNull()
        ->and($record[$this->nameCode])->toBe($shown)
        ->and($record[$this->weightCode])->toBe('3');
})->with([
    'formatted text'           => ['<p>Hello <b>world</b></p>', 'Hello world'],
    'script payload'           => ['<img src=x onerror=alert(1)>Washer', 'Washer'],
    'tag next to a stray "<"'  => ['Washer <5mm <b>thick</b>', 'Washer <5mm thick'],
]);

it('keeps the other attribute columns filled when one value contains a less-than sign', function () {
    $product = createSimpleProductWithValues($this->family, [
        $this->nameCode   => 'Washer <5mm thick',
        $this->weightCode => '7',
    ]);

    $record = productGridRecord($product->id, [$this->nameCode, $this->weightCode]);

    expect($record)->not->toBeNull()
        ->and($record[$this->weightCode])->toBe('7');
});

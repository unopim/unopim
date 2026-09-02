<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

/**
 * What an imported row is allowed to write.
 *
 * Two rules the product form already enforces and the importer did not: a row
 * may only carry values for attributes its own level owns
 * (@see ProductRepository), and media belongs under the product that owns it
 * rather than wherever the job's images directory happened to put it.
 *
 * The importer caches the catalogue for the lifetime of a job and refreshes it
 * in setImport(). Tests create their attributes after construction and never
 * start a job, so the cache has to be dropped for them to be seen.
 */
function freshImporter(): Importer
{
    foreach (['staticInitCache', 'staticInitCacheJobId'] as $property) {
        $reflected = new ReflectionProperty(Importer::class, $property);
        $reflected->setValue(null, null);
    }

    return resolve(Importer::class);
}

/**
 * An import resolves a media value against the job's images directory and used
 * to store that source path verbatim, so the file kept living under the
 * extraction directory instead of the product's own media folder — unlike the
 * product form, which stores through FileStorer under `product/{id}/{code}`.
 *
 * The importer caches the catalogue for the lifetime of a job and refreshes it
 * in setImport(). Tests create their attributes after construction and never
 * start a job, so the cache has to be dropped for them to be seen.
 */
function importedMediaProduct(string $type, string|array $value, string $source): array
{
    $attribute = Attribute::factory()->create([
        'code' => 'import_media_'.uniqid(),
        'type' => $type,
    ]);

    $product = Product::factory()->create([
        'values' => ['common' => [$attribute->code => $value]],
    ]);

    Storage::disk('public')->put($source, 'binary');

    return [$product, $attribute];
}

it('moves an imported image under the product it belongs to', function () {
    Storage::fake('public');

    $source = 'import-images/product-1786683098/product/15/image/aHashedFolder/bikes.jpeg';

    [$product, $attribute] = importedMediaProduct('image', $source, $source);

    freshImporter()->relocateMediaValues([$product->id]);

    $stored = $product->refresh()->values['common'][$attribute->code];

    expect($stored)->toMatch('#^product/'.$product->id.'/'.$attribute->code.'/[A-Za-z0-9]{40}/bikes\.jpeg$#')
        ->and(Storage::disk('public')->exists($stored))->toBeTrue();
});

it('moves every path of an imported gallery', function () {
    Storage::fake('public');

    $first = 'import-images/product-1/product/15/gallery/hashOne/one.jpeg';
    $second = 'import-images/product-1/product/15/gallery/hashTwo/two.jpeg';

    [$product, $attribute] = importedMediaProduct('gallery', [$first, $second], $first);

    Storage::disk('public')->put($second, 'binary');

    freshImporter()->relocateMediaValues([$product->id]);

    $stored = $product->refresh()->values['common'][$attribute->code];

    expect($stored)->toHaveCount(2);

    foreach ($stored as $path) {
        expect($path)->toStartWith('product/'.$product->id.'/'.$attribute->code.'/')
            ->and(Storage::disk('public')->exists($path))->toBeTrue();
    }
});

it('leaves a value that already sits under the product alone', function () {
    Storage::fake('public');

    $source = 'product/9999/image/anExistingHashedFolder/kept.jpeg';

    [$product, $attribute] = importedMediaProduct('file', $source, $source);

    $canonical = 'product/'.$product->id.'/'.$attribute->code.'/anExistingHashedFolder/kept.jpeg';

    Storage::disk('public')->put($canonical, 'binary');

    $product->values = ['common' => [$attribute->code => $canonical]];
    $product->save();

    freshImporter()->relocateMediaValues([$product->id]);

    expect($product->refresh()->values['common'][$attribute->code])->toBe($canonical);
});

it('keeps a value whose file never made it into storage', function () {
    Storage::fake('public');

    $missing = 'import-images/product-1/product/15/image/hash/gone.jpeg';

    $attribute = Attribute::factory()->create(['code' => 'import_media_'.uniqid(), 'type' => 'image']);

    $product = Product::factory()->create([
        'values' => ['common' => [$attribute->code => $missing]],
    ]);

    freshImporter()->relocateMediaValues([$product->id]);

    expect($product->refresh()->values['common'][$attribute->code])->toBe($missing);
});

/**
 * An import writes every column of a row onto that row's product, without
 * asking whether the row's level owns the attribute. A child's own value
 * shadows the one it should inherit, so changing the configurable leaves the
 * children showing whatever the file first gave them.
 *
 * @see ProductRepository — the rule the form and API enforce
 */
function placementTree(string $imageLevel): array
{
    $family = AttributeFamily::factory()->create();

    $colour = Attribute::factory()->create(['code' => 'colour_'.Str::random(6), 'type' => 'select']);
    $image = Attribute::factory()->create(['code' => 'image_'.Str::random(6), 'type' => 'image']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'st_'.Str::random(8),
        'name'                => 'Placement',
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $colour->id, 'level' => 'level_1', 'position' => 0],
    ]);

    if ($imageLevel !== 'common') {
        VariantStructureAttribute::insert([
            ['variant_structure_id' => $structure->id, 'attribute_id' => $image->id, 'level' => $imageLevel],
        ]);
    }

    $sku = 'placement-'.Str::random(6);

    $configurable = Product::factory()->create([
        'sku'                  => $sku,
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
        'values'               => ['common' => ['sku' => $sku, $image->code => 'product/1/image/parent.png']],
    ]);

    $group = Product::factory()->create([
        'sku'                 => $sku.'-green',
        'type'                => 'variant_group',
        'parent_id'           => $configurable->id,
        'attribute_family_id' => $family->id,
        'values'              => ['common' => ['sku' => $sku.'-green', $image->code => 'product/2/image/group.png']],
    ]);

    $simple = Product::factory()->create([
        'sku'                 => $sku.'-green-m',
        'type'                => 'simple',
        'parent_id'           => $group->id,
        'attribute_family_id' => $family->id,
        'values'              => ['common' => ['sku' => $sku.'-green-m', $image->code => 'product/3/image/simple.png']],
    ]);

    return [$configurable, $group, $simple, $image->code];
}

it('drops a value the imported row is not allowed to own', function () {
    [$configurable, $group, $simple, $code] = placementTree('common');

    resolve(Importer::class)->stripUnownedValues([$configurable->id, $group->id, $simple->id]);

    expect($configurable->refresh()->values['common'][$code] ?? null)->toBe('product/1/image/parent.png')
        ->and($group->refresh()->values['common'][$code] ?? null)->toBeNull()
        ->and($simple->refresh()->values['common'][$code] ?? null)->toBeNull();
});

it('keeps a value on the level the structure places it at', function () {
    [$configurable, $group, $simple, $code] = placementTree('variant');

    resolve(Importer::class)->stripUnownedValues([$configurable->id, $group->id, $simple->id]);

    expect($simple->refresh()->values['common'][$code] ?? null)->toBe('product/3/image/simple.png')
        ->and($configurable->refresh()->values['common'][$code] ?? null)->toBeNull()
        ->and($group->refresh()->values['common'][$code] ?? null)->toBeNull();
});

it('never strips the sku that identifies each row', function () {
    [$configurable, $group, $simple] = placementTree('common');

    resolve(Importer::class)->stripUnownedValues([$configurable->id, $group->id, $simple->id]);

    foreach ([$configurable, $group, $simple] as $product) {
        expect($product->refresh()->values['common']['sku'] ?? null)->toBe($product->sku);
    }
});

it('leaves a product outside any variant structure untouched', function () {
    $attribute = Attribute::factory()->create(['code' => 'plain_'.Str::random(6), 'type' => 'image']);

    $product = Product::factory()->create([
        'type'   => 'simple',
        'values' => ['common' => [$attribute->code => 'product/9/image/kept.png']],
    ]);

    resolve(Importer::class)->stripUnownedValues([$product->id]);

    expect($product->refresh()->values['common'][$attribute->code])->toBe('product/9/image/kept.png');
});

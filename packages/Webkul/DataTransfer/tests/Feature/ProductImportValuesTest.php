<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Enums\VariantLevel;
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

    if ($imageLevel !== VariantLevel::Common->value) {
        VariantStructureAttribute::insert([
            ['variant_structure_id' => $structure->id, 'attribute_id' => $image->id, 'level' => $imageLevel],
        ]);
    }

    $sku = 'placement-'.Str::random(6);

    $configurable = Product::factory()->create([
        'sku'                  => $sku,
        'type'                 => ProductType::Configurable->value,
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
        'values'               => ['common' => ['sku' => $sku, $image->code => 'product/1/image/parent.png']],
    ]);

    $group = Product::factory()->create([
        'sku'                 => $sku.'-green',
        'type'                => ProductType::VariantGroup->value,
        'parent_id'           => $configurable->id,
        'attribute_family_id' => $family->id,
        'values'              => ['common' => ['sku' => $sku.'-green', $image->code => 'product/2/image/group.png']],
    ]);

    $simple = Product::factory()->create([
        'sku'                 => $sku.'-green-m',
        'type'                => ProductType::Simple->value,
        'parent_id'           => $group->id,
        'attribute_family_id' => $family->id,
        'values'              => ['common' => ['sku' => $sku.'-green-m', $image->code => 'product/3/image/simple.png']],
    ]);

    return [$configurable, $group, $simple, $image->code];
}

it('drops a value the imported row is not allowed to own', function () {
    [$configurable, $group, $simple, $code] = placementTree(VariantLevel::Common->value);

    resolve(Importer::class)->stripUnownedValues([$configurable->id, $group->id, $simple->id]);

    expect($configurable->refresh()->values['common'][$code] ?? null)->toBe('product/1/image/parent.png')
        ->and($group->refresh()->values['common'][$code] ?? null)->toBeNull()
        ->and($simple->refresh()->values['common'][$code] ?? null)->toBeNull();
});

it('keeps a value on the level the structure places it at', function () {
    [$configurable, $group, $simple, $code] = placementTree(VariantLevel::Variant->value);

    resolve(Importer::class)->stripUnownedValues([$configurable->id, $group->id, $simple->id]);

    expect($simple->refresh()->values['common'][$code] ?? null)->toBe('product/3/image/simple.png')
        ->and($configurable->refresh()->values['common'][$code] ?? null)->toBeNull()
        ->and($group->refresh()->values['common'][$code] ?? null)->toBeNull();
});

it('never strips the sku that identifies each row', function () {
    [$configurable, $group, $simple] = placementTree(VariantLevel::Common->value);

    resolve(Importer::class)->stripUnownedValues([$configurable->id, $group->id, $simple->id]);

    foreach ([$configurable, $group, $simple] as $product) {
        expect($product->refresh()->values['common']['sku'] ?? null)->toBe($product->sku);
    }
});

it('leaves a product outside any variant structure untouched', function () {
    $attribute = Attribute::factory()->create(['code' => 'plain_'.Str::random(6), 'type' => 'image']);

    $product = Product::factory()->create([
        'type'   => ProductType::Simple->value,
        'values' => ['common' => [$attribute->code => 'product/9/image/kept.png']],
    ]);

    resolve(Importer::class)->stripUnownedValues([$product->id]);

    expect($product->refresh()->values['common'][$attribute->code])->toBe('product/9/image/kept.png');
});

/**
 * Two-level variant structure whose levels own values scoped per channel,
 * per locale and per channel and locale, including prices keyed by currency.
 *
 * @return array{suffix: string, family: AttributeFamily, structure: VariantStructure, color: Attribute, size: Attribute, codes: array<string, string>}
 */
function scopedPlacementTree(): array
{
    $suffix = Str::lower(Str::random(6));

    $color = Attribute::factory()->create(['code' => 'scolor_'.$suffix, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => 'ssize_'.$suffix, 'type' => 'select']);

    $scoped = fn (string $code, string $type, int $perLocale, int $perChannel): Attribute => Attribute::factory()->create([
        'code'              => $code.'_'.$suffix,
        'type'              => $type,
        'value_per_locale'  => $perLocale,
        'value_per_channel' => $perChannel,
        'is_required'       => 0,
    ]);

    $codes = [
        'group_price'   => [$scoped('sgprice', 'price', 1, 1), VariantLevel::SubParent->value],
        'group_text'    => [$scoped('sgtext', 'text', 1, 1), VariantLevel::SubParent->value],
        'variant_price' => [$scoped('svprice', 'price', 0, 1), VariantLevel::Variant->value],
        'locale_price'  => [$scoped('slprice', 'price', 1, 0), VariantLevel::Variant->value],
        'variant_text'  => [$scoped('svtext', 'text', 1, 1), VariantLevel::Variant->value],
        'root_text'     => [$scoped('srtext', 'text', 1, 1), VariantLevel::Common->value],
    ];

    $family = AttributeFamily::factory()->create(['code' => 'sfam_'.$suffix]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('id', array_merge(
        [$color->id, $size->id],
        array_map(fn (array $entry): int => $entry[0]->id, $codes),
    ))->get());

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'sst_'.$suffix,
        'name'                => 'Scoped Placement',
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    VariantStructureAttribute::insert(array_values(array_map(fn (array $entry): array => [
        'variant_structure_id' => $structure->id,
        'attribute_id'         => $entry[0]->id,
        'level'                => $entry[1],
    ], $codes)));

    return [
        'suffix'    => $suffix,
        'family'    => $family,
        'structure' => $structure,
        'color'     => $color,
        'size'      => $size,
        'codes'     => array_map(fn (array $entry): string => $entry[0]->code, $codes),
    ];
}

/**
 * Configurable, variant group and simple built on a scoped placement tree.
 *
 * @param  array{suffix: string, family: AttributeFamily, structure: VariantStructure}  $tree
 * @param  array<string, mixed>  $groupValues
 * @param  array<string, mixed>  $simpleValues
 * @return array{0: Product, 1: Product, 2: Product}
 */
function scopedPlacementProducts(array $tree, array $groupValues, array $simpleValues): array
{
    $sku = 'scoped-'.$tree['suffix'];

    $configurable = Product::factory()->create([
        'sku'                  => $sku,
        'type'                 => ProductType::Configurable->value,
        'attribute_family_id'  => $tree['family']->id,
        'variant_structure_id' => $tree['structure']->id,
        'values'               => ['common' => ['sku' => $sku]],
    ]);

    $group = Product::factory()->create([
        'sku'                 => $sku.'-g',
        'type'                => ProductType::VariantGroup->value,
        'parent_id'           => $configurable->id,
        'attribute_family_id' => $tree['family']->id,
        'values'              => ['common' => ['sku' => $sku.'-g']] + $groupValues,
    ]);

    $simple = Product::factory()->create([
        'sku'                 => $sku.'-g-v',
        'type'                => ProductType::Simple->value,
        'parent_id'           => $group->id,
        'attribute_family_id' => $tree['family']->id,
        'values'              => ['common' => ['sku' => $sku.'-g-v']] + $simpleValues,
    ]);

    return [$configurable, $group, $simple];
}

it('keeps the scoped values each variant level owns', function () {
    $tree = scopedPlacementTree();
    $codes = $tree['codes'];

    [$configurable, $group, $simple] = scopedPlacementProducts($tree, [
        'channel_locale_specific' => ['default' => ['en_US' => [
            $codes['group_price'] => ['USD' => '15'],
            $codes['group_text']  => 'Group text',
        ]]],
    ], [
        'channel_specific'        => ['default' => [$codes['variant_price'] => ['USD' => '21']]],
        'locale_specific'         => ['en_US' => [$codes['locale_price'] => ['USD' => '23']]],
        'channel_locale_specific' => ['default' => ['en_US' => [$codes['variant_text'] => 'Variant text']]],
    ]);

    resolve(Importer::class)->stripUnownedValues([$configurable->id, $group->id, $simple->id]);

    $group->refresh();
    $simple->refresh();

    expect(data_get($group->values, "channel_locale_specific.default.en_US.{$codes['group_price']}"))->toBe(['USD' => '15'])
        ->and(data_get($group->values, "channel_locale_specific.default.en_US.{$codes['group_text']}"))->toBe('Group text')
        ->and(data_get($simple->values, "channel_specific.default.{$codes['variant_price']}"))->toBe(['USD' => '21'])
        ->and(data_get($simple->values, "locale_specific.en_US.{$codes['locale_price']}"))->toBe(['USD' => '23'])
        ->and(data_get($simple->values, "channel_locale_specific.default.en_US.{$codes['variant_text']}"))->toBe('Variant text');
});

it('drops a scoped value the variant level does not own', function () {
    $tree = scopedPlacementTree();
    $codes = $tree['codes'];

    [$configurable, $group, $simple] = scopedPlacementProducts($tree, [
        'channel_locale_specific' => ['default' => ['en_US' => [
            $codes['group_text']   => 'Group text',
            $codes['root_text']    => 'Root text',
            $codes['variant_text'] => 'Variant text',
        ]]],
    ], [
        'channel_locale_specific' => ['default' => ['en_US' => [
            $codes['variant_text'] => 'Variant text',
            $codes['group_price']  => ['USD' => '15'],
        ]]],
    ]);

    resolve(Importer::class)->stripUnownedValues([$configurable->id, $group->id, $simple->id]);

    expect($group->refresh()->values['channel_locale_specific']['default']['en_US'])->toBe([$codes['group_text'] => 'Group text'])
        ->and($simple->refresh()->values['channel_locale_specific']['default']['en_US'])->toBe([$codes['variant_text'] => 'Variant text']);
});

it('imports the values placed at the sub-parent and variant levels', function () {
    $tree = scopedPlacementTree();
    $codes = $tree['codes'];
    $sku = 'scoped-import-'.$tree['suffix'];

    $row = [
        'locale'           => 'en_US',
        'channel'          => 'default',
        'attribute_family' => $tree['family']->code,
        'status'           => 'true',
    ];

    $rows = [
        $row + ['sku' => $sku, 'type' => ProductType::Configurable->value, 'parent' => '', 'variant_structure' => $tree['structure']->code],
        $row + [
            'sku'                           => $sku.'-g',
            'type'                          => ProductType::VariantGroup->value,
            'parent'                        => $sku,
            $tree['color']->code            => $tree['color']->options->first()->code,
            $codes['group_price'].' (USD)'  => '15',
            $codes['group_text']            => 'Group text',
        ],
        $row + [
            'sku'                            => $sku.'-g-v',
            'type'                           => ProductType::Simple->value,
            'parent'                         => $sku.'-g',
            $tree['size']->code              => $tree['size']->options->first()->code,
            $codes['variant_price'].' (USD)' => '21',
            $codes['locale_price'].' (USD)'  => '23',
            $codes['variant_text']           => 'Variant text',
        ],
    ];

    $jobTrack = JobTrack::factory()->create(['type' => 'import', 'action' => 'append']);

    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => $rows,
    ]);

    freshImporter()->setImport($jobTrack)->importBatch($batch->refresh());

    $group = Product::where('sku', $sku.'-g')->firstOrFail();
    $simple = Product::where('sku', $sku.'-g-v')->firstOrFail();

    expect(data_get($group->values, "channel_locale_specific.default.en_US.{$codes['group_price']}"))->toBe(['USD' => '15'])
        ->and(data_get($group->values, "channel_locale_specific.default.en_US.{$codes['group_text']}"))->toBe('Group text')
        ->and(data_get($simple->values, "channel_specific.default.{$codes['variant_price']}"))->toBe(['USD' => '21'])
        ->and(data_get($simple->values, "locale_specific.en_US.{$codes['locale_price']}"))->toBe(['USD' => '23'])
        ->and(data_get($simple->values, "channel_locale_specific.default.en_US.{$codes['variant_text']}"))->toBe('Variant text');
});

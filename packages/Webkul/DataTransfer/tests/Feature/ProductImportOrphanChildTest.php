<?php

use Illuminate\Support\Str;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\Product\Models\Product;

use function Pest\Laravel\assertDatabaseMissing;

/**
 * A child row validates against its parent's row in the file, not against a
 * parent that will actually exist. When the parent row is rejected — a missing
 * required attribute, say — the children still passed and were inserted with a
 * null parent_id, leaving a variant group or simple hanging outside any tree.
 */
function insertRowFor(string $type, string $sku, ?string $parent): array
{
    return [
        'type'                => $type,
        'sku'                 => $sku,
        'parent'              => $parent,
        'parent_id'           => null,
        'attribute_family_id' => AttributeFamily::factory()->create()->id,
        'values'              => ['common' => ['sku' => $sku]],
    ];
}

it('does not create a child whose parent never made it into the catalogue', function () {
    $missingParent = 'absent-'.Str::random(6);
    $childSku = $missingParent.'-green';

    resolve(Importer::class)->saveProducts([
        'insert' => [$childSku => insertRowFor('variant_group', $childSku, $missingParent)],
    ]);

    assertDatabaseMissing($this->getFullTableName(Product::class), ['sku' => $childSku]);
});

it('drops the whole branch when the configurable is rejected', function () {
    $parent = 'absent-'.Str::random(6);
    $group = $parent.'-green';
    $simple = $group.'-m';

    resolve(Importer::class)->saveProducts([
        'insert' => [
            $group  => insertRowFor('variant_group', $group, $parent),
            $simple => insertRowFor('simple', $simple, $group),
        ],
    ]);

    assertDatabaseMissing($this->getFullTableName(Product::class), ['sku' => $group]);
    assertDatabaseMissing($this->getFullTableName(Product::class), ['sku' => $simple]);
});

it('still creates a child whose parent exists', function () {
    $family = AttributeFamily::factory()->create();

    $parent = Product::factory()->create([
        'sku'                 => 'present-'.Str::random(6),
        'type'                => 'configurable',
        'attribute_family_id' => $family->id,
    ]);

    $childSku = $parent->sku.'-green';

    $importer = resolve(Importer::class);

    $importer->saveProducts([
        'insert' => [$childSku => insertRowFor('variant_group', $childSku, $parent->sku)],
    ]);

    $child = Product::query()->where('sku', $childSku)->first();

    expect($child)->not->toBeNull()
        ->and($child->parent_id)->toBe($parent->id);
});

it('still creates a row that declares no parent at all', function () {
    $sku = 'standalone-'.Str::random(6);

    resolve(Importer::class)->saveProducts([
        'insert' => [$sku => insertRowFor('simple', $sku, null)],
    ]);

    expect(Product::query()->where('sku', $sku)->exists())->toBeTrue();
});

it('reports every dropped row against the line it came from', function () {
    $parent = 'absent-'.Str::random(6);
    $group = $parent.'-green';
    $simple = $group.'-m';

    $importer = resolve(Importer::class);

    $errors = resolve(Error::class);

    (new ReflectionProperty($importer, 'errorHelper'))->setValue($importer, $errors);

    (new ReflectionProperty($importer, 'fileRowsBySku'))
        ->setValue($importer, [
            $group  => ['row' => 2, 'parent' => $parent],
            $simple => ['row' => 3, 'parent' => $group],
        ]);

    $importer->saveProducts([
        'insert' => [
            $group  => insertRowFor('variant_group', $group, $parent),
            $simple => insertRowFor('simple', $simple, $group),
        ],
    ]);

    expect($errors->getErrorsCount())->toBe(2)
        ->and(array_keys($errors->getAllErrors()))->toEqualCanonicalizing([2, 3]);
});

it('marks a child invalid when its parent row failed', function () {
    $rows = [
        1 => ['sku' => 'parent', 'parent' => null],
        2 => ['sku' => 'parent-green', 'parent' => 'parent'],
        3 => ['sku' => 'parent-green-m', 'parent' => 'parent-green'],
    ];

    $orphans = resolve(Importer::class)->orphanRowNumbers($rows, [1 => true]);

    expect($orphans)->toEqualCanonicalizing([2, 3]);
});

it('leaves a branch alone when its parent row passed', function () {
    $rows = [
        1 => ['sku' => 'parent', 'parent' => null],
        2 => ['sku' => 'parent-green', 'parent' => 'parent'],
    ];

    expect(resolve(Importer::class)->orphanRowNumbers($rows, []))->toBe([]);
});

it('ignores a parent that is not in the file, which the validator already reports', function () {
    $rows = [
        2 => ['sku' => 'orphan', 'parent' => 'never-in-file'],
    ];

    expect(resolve(Importer::class)->orphanRowNumbers($rows, []))->toBe([]);
});

it('does not re-report a row that already failed on its own', function () {
    $rows = [
        1 => ['sku' => 'parent', 'parent' => null],
        2 => ['sku' => 'parent-green', 'parent' => 'parent'],
    ];

    expect(resolve(Importer::class)->orphanRowNumbers($rows, [1 => true, 2 => true]))->toBe([]);
});

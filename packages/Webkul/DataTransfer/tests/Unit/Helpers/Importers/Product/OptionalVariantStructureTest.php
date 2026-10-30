<?php

use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Helpers\Importers\Product\SKUStorage;
use Webkul\DataTransfer\Models\JobTrack;

/**
 * Exercise the real header validator without loading a catalogue or writing batches.
 */
function productHeaderImporter(array $columns): Importer
{
    $importer = Mockery::mock(Importer::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $importer->shouldReceive('initAttributes')->andReturnNull();
    $importer->shouldReceive('saveValidatedBatches')->andReturnSelf();
    $importer->setImport(new JobTrack(['id' => 1, 'action' => 'append']));
    $importer->setSource(new class($columns)
    {
        public function __construct(private array $columns) {}

        public function getColumnNames(): array
        {
            return $this->columns;
        }
    });
    $importer->setErrorHelper(new Error);

    // sku is the product identifier attribute in a real catalogue.
    (new ReflectionProperty(Importer::class, 'validColumnNames'))->setValue(
        $importer, array_merge(Importer::BASE_COLUMN_NAMES, ['sku'])
    );

    return $importer;
}

function productHeaderErrors(Importer $importer): Error
{
    return (new ReflectionProperty(Importer::class, 'errorHelper'))->getValue($importer);
}

it('accepts legacy product headers with configurable_attributes and no variant_structure', function () {
    $importer = productHeaderImporter([
        'sku', 'locale', 'channel', 'type', 'parent', 'attribute_family', 'configurable_attributes',
    ]);

    $importer->validateData();

    expect(productHeaderErrors($importer)->getErrorsCount())->toBe(0);
});

it('accepts explicit variant_structure headers without configurable_attributes', function () {
    $importer = productHeaderImporter([
        'sku', 'locale', 'channel', 'type', 'parent', 'attribute_family', 'variant_structure',
    ]);

    $importer->validateData();

    expect(productHeaderErrors($importer)->getErrorsCount())->toBe(0);
});

it('still rejects a missing required product header', function (string $missing) {
    $columns = ['sku', 'locale', 'channel', 'type', 'parent', 'attribute_family', 'variant_structure'];
    $importer = productHeaderImporter(array_values(array_diff($columns, [$missing])));

    $importer->validateData();

    expect(productHeaderErrors($importer)->getAllErrorsGroupedByCode())
        ->toHaveKey(Importer::ERROR_CODE_COLUMN_NOT_FOUND);
    expect(productHeaderErrors($importer)->getAllErrors()[''][0]['column'])->toBe($missing);
})->with(['sku', 'locale', 'channel', 'type', 'parent', 'attribute_family']);

it('does not inherit variant_structure as an attribute from a parent row', function (string $type) {
    config(['import.parallel_validation' => false]);

    $importer = Mockery::mock(Importer::class)->makePartial()->shouldAllowMockingProtectedMethods();
    // Only external catalogue reads, row validation and batch writes are replaced.
    // The production parent-row collection and inheritance logic both run here.
    foreach (['preloadCategoryCodes', 'preloadExistingProducts', 'preloadExistingUniqueValues',
        'sequentialValidateRows', 'createBatchesFromValidRows'] as $method) {
        $importer->shouldReceive($method)->once()->andReturnNull();
    }

    $importer->shouldReceive('getExistingProduct')->with('parent')->andReturnNull();

    $skuStorage = Mockery::mock(SKUStorage::class);
    $skuStorage->shouldReceive('load')->once()->with(['parent']);
    (new ReflectionProperty(Importer::class, 'skuStorage'))->setValue($importer, $skuStorage);

    $source = new class([['sku' => 'parent', 'type' => $type, 'parent' => '', 'attribute_family' => 'default', 'variant_structure' => 'by_color', 'name' => 'Example parent']]) extends ArrayIterator
    {
        public function getCurrentRowNumber(): int
        {
            return $this->key() + 1;
        }
    };

    $importer->setSource($source)->setErrorHelper(new Error);
    (new ReflectionMethod(Importer::class, 'saveValidatedBatches'))->invoke($importer);

    $parents = (new ReflectionProperty(Importer::class, 'fileParentRows'))->getValue($importer);
    expect($parents['parent']['structure'])->toBe('by_color')
        ->and($parents['parent']['attributes'])->toBe(['name']);

    $inherited = (new ReflectionMethod(Importer::class, 'attributesOwnedByAncestors'))->invoke(
        $importer, ['parent' => 'parent']
    );
    expect($inherited)->toContain('name')->not->toContain('variant_structure');
})->with(['configurable', 'variant_group']);

it('rejects an explicit unknown variant structure instead of falling back to legacy axes', function () {
    $importer = productHeaderImporter([]);
    $importer->shouldReceive('validChannelsAndLocale')->once()->andReturnTrue();
    $importer->shouldReceive('getVariantStructure')->once()
        ->with('missing-import-test-structure', 987654)->andReturnNull();
    (new ReflectionProperty(Importer::class, 'attributeFamiliesByCode'))->setValue(
        $importer, ['example_family' => (object) ['id' => 987654]]
    );

    $valid = $importer->validateRow([
        'sku'               => 'example-parent', 'type' => 'configurable', 'attribute_family' => 'example_family',
        'variant_structure' => 'missing-import-test-structure', 'configurable_attributes' => 'color',
    ], 1);

    expect($valid)->toBeFalse()
        ->and(productHeaderErrors($importer)->getAllErrors()[1][0])
        ->toMatchArray(['code' => Importer::ERROR_VARIANT_STRUCTURE_NOT_FOUND, 'column' => 'variant_structure']);
});

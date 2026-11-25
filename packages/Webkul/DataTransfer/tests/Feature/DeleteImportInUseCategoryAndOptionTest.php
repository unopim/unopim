<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\Category;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AttributeOption\Importer as AttributeOptionImporter;
use Webkul\DataTransfer\Helpers\Importers\Category\Importer as CategoryImporter;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Product\Models\Product;

/**
 * @param  array<string, mixed>  $values
 */
function importProductWithValues(array $values): void
{
    $product = Product::factory()->create();

    $values['common']['sku'] = $product->sku;

    $product->values = $values;
    $product->save();
}

/**
 * Build a delete importer with the storages its validateData() step would load.
 *
 * @param  class-string<CategoryImporter|AttributeOptionImporter>  $importerClass
 * @return array{0: CategoryImporter|AttributeOptionImporter, 1: Error}
 */
function deleteImporter(string $importerClass): array
{
    $error = app(Error::class);

    $importer = app($importerClass)->setImport(JobTrack::factory()->create(['type' => 'import', 'action' => Import::ACTION_DELETE]));

    $importer->setErrorHelper($error);

    (function (): void {
        if ($this instanceof CategoryImporter) {
            $this->categoryStorage->init();
            $this->getNonDeletableCategories();

            return;
        }

        $this->attributeOptionStorage->init();
        $this->attributeStorage->init();
    })->call($importer);

    return [$importer, $error];
}

it('rejects a category delete row while products use the category', function () {
    $category = Category::factory()->create();

    importProductWithValues(['categories' => [$category->code]]);

    [$importer, $error] = deleteImporter(CategoryImporter::class);

    expect($importer->validateRow(['code' => $category->code], 1))->toBeFalse()
        ->and($error->getAllErrorsGroupedByCode())->toHaveKey(CategoryImporter::ERROR_USED_BY_PRODUCTS);
});

it('accepts a category delete row no product uses', function () {
    $category = Category::factory()->create();

    expect(deleteImporter(CategoryImporter::class)[0]->validateRow(['code' => $category->code], 1))->toBeTrue();
});

it('rejects an attribute option delete row while products use the option', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    importProductWithValues(['common' => [$attribute->code => $option->code]]);

    [$importer, $error] = deleteImporter(AttributeOptionImporter::class);

    expect($importer->validateRow(['code' => $option->code, 'attribute_code' => $attribute->code], 1))->toBeFalse()
        ->and($error->getAllErrorsGroupedByCode())->toHaveKey(AttributeOptionImporter::ERROR_USED_BY_PRODUCTS);
});

it('accepts an attribute option delete row no product uses', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    expect(deleteImporter(AttributeOptionImporter::class)[0]->validateRow(['code' => $option->code, 'attribute_code' => $attribute->code], 1))->toBeTrue();
});

<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\Category;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AttributeOption\Importer as AttributeOptionImporter;
use Webkul\DataTransfer\Helpers\Importers\Category\Importer as CategoryImporter;
use Webkul\DataTransfer\Helpers\Sources\CSV;
use Webkul\DataTransfer\Models\JobTrack;

/**
 * Build a category delete importer with the storages its validateData() step would load.
 *
 * @return array{0: CategoryImporter, 1: Error}
 */
function inUseCategoryDeleteImporter(): array
{
    $error = app(Error::class);

    $importer = app(CategoryImporter::class)->setImport(JobTrack::factory()->create(['type' => 'import', 'action' => Import::ACTION_DELETE]));

    $importer->setErrorHelper($error);

    (function (): void {
        $this->categoryStorage->init();
        $this->getNonDeletableCategories();
    })->call($importer);

    return [$importer, $error];
}

it('rejects a category delete row while products use the category', function (): void {
    $category = Category::factory()->create();

    productReferencingCatalogValues(['categories' => [$category->code]]);

    [$importer, $error] = inUseCategoryDeleteImporter();

    expect($importer->validateRow(['code' => $category->code], 1))->toBeFalse()
        ->and($error->getAllErrorsGroupedByCode())->toHaveKey(CategoryImporter::ERROR_USED_BY_PRODUCTS);
});

it('accepts a category delete row no product uses', function (): void {
    $category = Category::factory()->create();

    expect(inUseCategoryDeleteImporter()[0]->validateRow(['code' => $category->code], 1))->toBeTrue();
});

/**
 * Validate an attribute option delete file through the importer, the way the import job does.
 *
 * @param  array<int, array{0: string, 1: string}>  $rows
 */
function validateAttributeOptionDeleteFile(array $rows): Error
{
    Storage::fake('private');

    $path = 'imports/option-delete.csv';

    Storage::disk('private')->put($path, implode("\n", ['code,attribute_code', ...array_map(fn (array $row): string => implode(',', $row), $rows)])."\n");

    $error = app(Error::class);

    app(AttributeOptionImporter::class)
        ->setImport(JobTrack::factory()->create(['type' => 'import', 'action' => Import::ACTION_DELETE]))
        ->setErrorHelper($error)
        ->setSource(new CSV($path))
        ->validateData();

    return $error;
}

it('rejects only the attribute option delete rows whose options products still use', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'multiselect']);
    [$usedOption, $unusedOption] = $attribute->options()->take(2)->get()->all();

    productReferencingCatalogValues(['common' => [$attribute->code => 'other_code,'.$usedOption->code]]);

    $error = validateAttributeOptionDeleteFile([
        [$usedOption->code, $attribute->code],
        [$unusedOption->code, $attribute->code],
    ]);

    expect($error->getAllErrorsGroupedByCode())->toHaveKey(AttributeOptionImporter::ERROR_USED_BY_PRODUCTS)
        ->and($error->isRowInvalid(1))->toBeTrue()
        ->and($error->isRowInvalid(2))->toBeFalse();
});

it('accepts an attribute option delete file no product uses', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    expect(validateAttributeOptionDeleteFile([[$option->code, $attribute->code]])->getAllErrorsGroupedByCode())
        ->not->toHaveKey(AttributeOptionImporter::ERROR_USED_BY_PRODUCTS);
});

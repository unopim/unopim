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
use Webkul\DataTransfer\Models\JobTrackBatch;

/**
 * Validate a category delete file through the importer, the way the import job does.
 *
 * @param  array<int, string>  $codes
 */
function validateCategoryDeleteFile(array $codes): Error
{
    Storage::fake('private');

    $path = 'imports/category-delete.csv';

    Storage::disk('private')->put($path, implode("\n", ['locale,code,parent', ...array_map(fn (string $code): string => 'en_US,'.$code.',', $codes)])."\n");

    $error = app(Error::class);

    app(CategoryImporter::class)
        ->setImport(JobTrack::factory()->create(['type' => 'import', 'action' => Import::ACTION_DELETE]))
        ->setErrorHelper($error)
        ->setSource(new CSV($path))
        ->validateData();

    return $error;
}

it('rejects only the category delete rows whose subtree products still use', function (): void {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $unused = Category::factory()->create();

    productReferencingCatalogValues(['categories' => [$child->code]]);

    $error = validateCategoryDeleteFile([$parent->code, $child->code, $unused->code]);

    expect($error->getAllErrorsGroupedByCode())->toHaveKey(CategoryImporter::ERROR_USED_BY_PRODUCTS)
        ->and($error->isRowInvalid(1))->toBeTrue()
        ->and($error->isRowInvalid(2))->toBeTrue()
        ->and($error->isRowInvalid(3))->toBeFalse();
});

it('accepts a category delete file no product uses', function (): void {
    $category = Category::factory()->create();

    expect(validateCategoryDeleteFile([$category->code])->getAllErrorsGroupedByCode())
        ->not->toHaveKey(CategoryImporter::ERROR_USED_BY_PRODUCTS);
});

it('keeps a category a product was assigned to after validation when the delete batch runs', function (): void {
    $assignedLater = Category::factory()->create();
    $unused = Category::factory()->create();

    productReferencingCatalogValues(['categories' => [$assignedLater->code]]);

    $jobTrack = JobTrack::factory()->create(['type' => 'import', 'action' => Import::ACTION_DELETE]);

    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => [['code' => $assignedLater->code], ['code' => $unused->code]],
    ]);

    app(CategoryImporter::class)->setImport($jobTrack)->importBatch($batch->refresh());

    expect(Category::query()->find($assignedLater->id))->not->toBeNull()
        ->and(Category::query()->find($unused->id))->toBeNull();
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

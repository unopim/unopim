<?php

namespace Tests\Webkul\DataTransfer\Unit\Helpers\Importers\Category;

use ReflectionClass;
use Webkul\Category\Models\CategoryField;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Importers\Attribute\Importer as AttributeImporter;
use Webkul\DataTransfer\Helpers\Importers\Category\Importer;
use Webkul\DataTransfer\Helpers\Importers\CategoryField\Importer as CategoryFieldImporter;
use Webkul\DataTransfer\Models\JobTrack;

function validColumnNamesOf(string $importer): array
{
    return (new ReflectionClass($importer))->getDefaultProperties()['validColumnNames'];
}

it('accepts the productCounts column written by the category export', function () {
    expect(validColumnNamesOf(Importer::class))->toContain('productCounts');
});

it('keeps the productCounts allowance consistent across catalog importers', function () {
    expect(validColumnNamesOf(AttributeImporter::class))->toContain('productCounts')
        ->and(validColumnNamesOf(CategoryFieldImporter::class))->toContain('productCounts');
});

it('validates a formula-escaped negative category field value as the number it will be stored as', function (): void {
    $field = CategoryField::factory()->create([
        'type'       => 'text',
        'validation' => 'number',
        'status'     => 1,
    ]);

    $importer = app(Importer::class)->setImport(JobTrack::factory()->create());
    $importer->setErrorHelper(app(Error::class));
    $importer->getCategoryFields();

    $isValid = $importer->validateRow([
        'code'       => 'escaped_'.uniqid(),
        'parent'     => '',
        'name'       => 'Escaped value',
        'locale'     => 'en_US',
        $field->code => EscapeFormulaOperators::escapeValue('-5'),
    ], 1);

    expect($isValid)->toBeTrue();
});

/**
 * @param  array<string, mixed>  $attributes
 * @return array{0: Importer, 1: CategoryField, 2: CategoryField}
 */
function categoryImporterWithTwoUniqueFields(array $attributes = []): array
{
    $first = CategoryField::factory()->create(['type' => 'text', 'is_unique' => 1, 'status' => 1, ...$attributes]);
    $second = CategoryField::factory()->create(['type' => 'text', 'is_unique' => 1, 'status' => 1, ...$attributes]);

    $importer = app(Importer::class)->setImport(JobTrack::factory()->create());
    $importer->setErrorHelper(app(Error::class));
    $importer->getCategoryFields();

    return [$importer, $first, $second];
}

/**
 * @param  array<string, string>  $values
 * @return array<string, string>
 */
function uniqueCategoryRow(array $values): array
{
    return [
        'code'   => 'unique_'.uniqid(),
        'parent' => '',
        'name'   => 'Unique value',
        'locale' => 'en_US',
        ...$values,
    ];
}

it('rejects an in-file duplicate of any unique category field, not only the last one checked', function (array $attributes): void {
    [$importer, $first, $second] = categoryImporterWithTwoUniqueFields($attributes);

    expect($importer->validateRow(uniqueCategoryRow([$first->code => 'first-a', $second->code => 'second-a']), 1))->toBeTrue()
        ->and($importer->validateRow(uniqueCategoryRow([$first->code => 'first-a', $second->code => 'second-b']), 2))->toBeFalse()
        ->and($importer->validateRow(uniqueCategoryRow([$first->code => 'first-c', $second->code => 'second-a']), 3))->toBeFalse();
})->with([
    'common'     => [[]],
    'per locale' => [['value_per_locale' => 1]],
]);

it('accepts the same unique category field value on another row of the same category', function (): void {
    [$importer, $first] = categoryImporterWithTwoUniqueFields(['value_per_locale' => 1]);

    $row = uniqueCategoryRow([$first->code => 'scoped-a']);

    expect($importer->validateRow($row, 1))->toBeTrue()
        ->and($importer->validateRow($row, 2))->toBeTrue();
});

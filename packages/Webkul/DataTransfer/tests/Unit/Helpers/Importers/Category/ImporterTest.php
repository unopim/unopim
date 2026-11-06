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

it('validates a formula-escaped negative category field value as the number it will be stored as', function () {
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

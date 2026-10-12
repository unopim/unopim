<?php

namespace Tests\Webkul\DataTransfer\Unit\Helpers\Importers\Category;

use ReflectionClass;
use Webkul\DataTransfer\Helpers\Importers\Attribute\Importer as AttributeImporter;
use Webkul\DataTransfer\Helpers\Importers\Category\Importer;
use Webkul\DataTransfer\Helpers\Importers\CategoryField\Importer as CategoryFieldImporter;

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

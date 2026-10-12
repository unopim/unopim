<?php

use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Attribute\Models\Attribute;

function fileColumnFor(Attribute $attribute): array
{
    $grid = app(ProductDataGrid::class);

    $method = new ReflectionMethod($grid, 'buildColumnDefinition');
    $method->setAccessible(true);

    return $method->invoke($grid, $attribute);
}

it('renders a file attribute column as the file name instead of the stored path', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create([
        'code' => 'spec_sheet_'.uniqid(),
        'type' => Attribute::FILE_ATTRIBUTE_TYPE,
    ]);

    $column = fileColumnFor($attribute);

    expect($column['closure'] ?? null)->not->toBeNull();

    $closure = $column['closure'];

    expect($closure('product/1/abcdef/datasheet.pdf'))->toBe('datasheet.pdf')
        ->and($closure(['product/1/abcdef/manual.pdf']))->toBe('manual.pdf')
        ->and($closure(null))->toBe('')
        ->and($closure(''))->toBe('');
});

it('escapes a file name so a crafted upload cannot inject markup', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create([
        'code' => 'spec_sheet_'.uniqid(),
        'type' => Attribute::FILE_ATTRIBUTE_TYPE,
    ]);

    $closure = fileColumnFor($attribute)['closure'];

    expect($closure('product/1/abcdef/<img src=x onerror=alert(1)>.pdf'))
        ->not->toContain('<img');
});

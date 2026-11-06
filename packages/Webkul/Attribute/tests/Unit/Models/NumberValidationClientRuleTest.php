<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\CategoryField;
use Webkul\Product\Models\AssociationTypeField;

it('maps number validation to a client rule that accepts signed decimals', function (string $model) {
    $field = new $model;
    $field->type = 'text';
    $field->validation = 'number';

    expect($field->getValidationsField())
        ->toContain('decimal: true')
        ->not->toContain('numeric: true');
})->with([
    Attribute::class,
    CategoryField::class,
    AssociationTypeField::class,
]);

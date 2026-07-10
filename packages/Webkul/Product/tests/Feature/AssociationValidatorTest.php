<?php

use Illuminate\Validation\ValidationException;
use Webkul\Product\Repositories\AssociationTypeRepository;
use Webkul\Product\Validator\AssociationValidator;

function createAssociationTypeWithQuantityField(): int
{
    $repo = app(AssociationTypeRepository::class);

    $type = $repo->create([
        'code'            => 'spare_parts_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Spare Parts'],
        'fields'          => [
            [
                'code'        => 'quantity',
                'type'        => 'text',
                'validation'  => 'number',
                'is_required' => 1,
                'status'      => 1,
                'section'     => 'left',
                'en_US'       => ['name' => 'Quantity'],
            ],
        ],
    ]);

    return $type->id;
}

it('throws when a field value fails its validation rule', function () {
    $typeId = createAssociationTypeWithQuantityField();
    $validator = app(AssociationValidator::class);

    expect(fn () => $validator->validate($typeId, ['common' => ['quantity' => 'abc']]))
        ->toThrow(ValidationException::class);
});

it('passes when field values are valid', function () {
    $typeId = createAssociationTypeWithQuantityField();
    $validator = app(AssociationValidator::class);

    $validator->validate($typeId, ['common' => ['quantity' => '2']]);
})->throwsNoExceptions();

it('throws when a required field is missing', function () {
    $typeId = createAssociationTypeWithQuantityField();
    $validator = app(AssociationValidator::class);

    expect(fn () => $validator->validate($typeId, ['common' => []]))
        ->toThrow(ValidationException::class);
});

it('throws when an unknown field code is present', function () {
    $typeId = createAssociationTypeWithQuantityField();
    $validator = app(AssociationValidator::class);

    expect(fn () => $validator->validate($typeId, ['common' => ['quantity' => '2', 'bogus' => 'x']]))
        ->toThrow(ValidationException::class);
});

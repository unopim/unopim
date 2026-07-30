<?php

use Webkul\Product\Models\AssociationType;
use Webkul\Product\Models\AssociationTypeField;
use Webkul\Product\Repositories\AssociationTypeRepository;

function staleIdAssociationType(): AssociationType
{
    return app(AssociationTypeRepository::class)->create([
        'code'            => 'stale_id_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Stale Id'],
    ]);
}

it('ignores a delete for a field id that no longer exists', function () {
    $associationType = staleIdAssociationType();

    $repository = app(AssociationTypeRepository::class);

    $repository->update([
        'fields' => [
            999999 => [
                'isNew'    => false,
                'isDelete' => true,
                'code'     => 'ghost_field',
                'type'     => 'text',
            ],
        ],
    ], $associationType->id);

    expect(AssociationTypeField::where('association_type_id', $associationType->id)->count())->toBe(0);
});

it('does not touch a field belonging to another association type', function () {
    $owner = staleIdAssociationType();
    $other = staleIdAssociationType();

    $repository = app(AssociationTypeRepository::class);

    $repository->update([
        'fields' => [
            'new_1' => [
                'isNew'    => true,
                'isDelete' => false,
                'code'     => 'owned_field',
                'type'     => 'text',
                'status'   => 1,
                'position' => 0,
                'en_US'    => ['name' => 'Owned Field'],
            ],
        ],
    ], $owner->id);

    $ownedField = AssociationTypeField::where('association_type_id', $owner->id)->firstOrFail();

    $repository->update([
        'fields' => [
            $ownedField->id => [
                'isNew'    => false,
                'isDelete' => true,
                'code'     => 'owned_field',
                'type'     => 'text',
            ],
        ],
    ], $other->id);

    expect(AssociationTypeField::find($ownedField->id))->not->toBeNull();
});

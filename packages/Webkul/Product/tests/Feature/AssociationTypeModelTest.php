<?php

use Webkul\Product\Models\AssociationType;
use Webkul\Product\Models\AssociationTypeField;

it('creates an association type with a translated name and a field', function () {
    $type = AssociationType::create([
        'code'            => 'bundle_kit',
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
    ]);

    $type->translations()->create(['locale' => 'en_US', 'name' => 'Bundle / Kit']);

    $field = AssociationTypeField::create([
        'association_type_id' => $type->id,
        'code'                => 'quantity',
        'type'                => 'text',
        'validation'          => 'number',
        'is_required'         => 1,
        'status'              => 1,
        'section'             => 'left',
    ]);

    expect($type->fresh()->name)->toBe('Bundle / Kit')
        ->and($type->fields)->toHaveCount(1)
        ->and($field->associationType->id)->toBe($type->id);
});

<?php

use Webkul\Product\Repositories\AssociationTypeRepository;

it('creates a type with translations and a field via the repository', function () {
    $repo = app(AssociationTypeRepository::class);

    $type = $repo->create([
        'code'            => 'spare_parts',
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Spare Parts'],
        'fields'          => [
            ['code' => 'position', 'type' => 'text', 'validation' => 'number', 'status' => 1, 'section' => 'left', 'en_US' => ['name' => 'Position']],
        ],
    ]);

    expect($type->code)->toBe('spare_parts')
        ->and($type->name)->toBe('Spare Parts')
        ->and($type->fields)->toHaveCount(1)
        ->and($type->fields->first()->code)->toBe('position')
        ->and($type->fields->first()->name)->toBe('Position')
        ->and($type->fields->first()->association_type_id)->toBe($type->id);

    expect($repo->getActiveTypes()->pluck('code')->all())->toContain('spare_parts');
});

it('updates a type, its translation, and manages fields (new/update/delete)', function () {
    $repo = app(AssociationTypeRepository::class);

    $type = $repo->create([
        'code'            => 'cross_sell',
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Cross Sell'],
        'fields'          => [
            ['code' => 'note', 'type' => 'text', 'validation' => null, 'status' => 1, 'section' => 'left', 'en_US' => ['name' => 'Note']],
        ],
    ]);

    $existingField = $type->fields->first();

    $updated = $repo->update([
        'code'            => 'cross_sell',
        'status'          => 1,
        'position'        => 2,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Cross Sell Updated'],
        'fields'          => [
            $existingField->id => [
                'isNew'    => 'false',
                'isDelete' => 'true',
            ],
            'new-1' => [
                'isNew'      => 'true',
                'code'       => 'priority',
                'type'       => 'text',
                'status'     => 1,
                'section'    => 'right',
                'en_US'      => ['name' => 'Priority'],
            ],
        ],
    ], $type->id);

    expect($updated->name)->toBe('Cross Sell Updated')
        ->and($updated->position)->toBe(2)
        ->and($updated->fields)->toHaveCount(1)
        ->and($updated->fields->first()->code)->toBe('priority');
});

it('gets only active types ordered by position with translations and fields eager loaded', function () {
    $repo = app(AssociationTypeRepository::class);

    $repo->create([
        'code'            => 'inactive_type',
        'status'          => 0,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Inactive Type'],
    ]);

    $repo->create([
        'code'            => 'active_type',
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Active Type'],
    ]);

    $activeTypes = $repo->getActiveTypes();

    expect($activeTypes->pluck('code')->all())->toContain('active_type')
        ->and($activeTypes->pluck('code')->all())->not->toContain('inactive_type')
        ->and($activeTypes->first()->relationLoaded('translations'))->toBeTrue()
        ->and($activeTypes->first()->relationLoaded('fields'))->toBeTrue();
});

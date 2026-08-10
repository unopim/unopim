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
            ['code' => 'position', 'type' => 'text', 'validation' => 'number', 'status' => 1, 'en_US' => ['name' => 'Position']],
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

it('loads only the requested active types via getActiveTypesByIds', function () {
    $repo = app(AssociationTypeRepository::class);

    $wanted = $repo->create([
        'code'            => 'wanted_type_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Wanted'],
    ]);

    $disabled = $repo->create([
        'code'            => 'disabled_type_'.uniqid(),
        'status'          => 0,
        'position'        => 2,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Disabled'],
    ]);

    $other = $repo->create([
        'code'            => 'other_type_'.uniqid(),
        'status'          => 1,
        'position'        => 3,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Other'],
    ]);

    $result = $repo->getActiveTypesByIds([$wanted->id, $disabled->id]);

    expect($result->pluck('id')->all())->toContain($wanted->id)
        ->and($result->pluck('id')->all())->not->toContain($disabled->id)
        ->and($result->pluck('id')->all())->not->toContain($other->id);

    expect($repo->getActiveTypesByIds([])->all())->toBe([]);
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
            ['code' => 'note', 'type' => 'text', 'validation' => null, 'status' => 1, 'en_US' => ['name' => 'Note']],
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

it('does not duplicate a field when the same still-new payload is submitted twice', function () {
    $repo = app(AssociationTypeRepository::class);

    $type = $repo->create([
        'code'            => 'resubmit_type_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Resubmit'],
    ]);

    $payload = [
        'status'   => 1,
        'position' => 1,
        'en_US'    => ['name' => 'Resubmit'],
        'fields'   => [
            'new_0' => [
                'isNew'    => 'true',
                'isDelete' => 'false',
                'code'     => 'feature',
                'type'     => 'multiselect',
                'status'   => 1,
                'position' => 0,
                'en_US'    => ['name' => 'Feature'],
                'options'  => [
                    'option_0' => ['isNew' => 'true', 'isDelete' => 'false', 'code' => 'gps', 'sort_order' => 0, 'en_US' => ['label' => 'GPS']],
                ],
            ],
        ],
    ];

    $repo->update($payload, $type->id);
    $repo->update($payload, $type->id);

    $fields = $type->refresh()->fields;

    expect($fields)->toHaveCount(1)
        ->and($fields->first()->code)->toBe('feature')
        ->and($fields->first()->options)->toHaveCount(1)
        ->and($fields->first()->options->first()->code)->toBe('gps');
});

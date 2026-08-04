<?php

use Illuminate\Support\Str;
use Webkul\Product\Repositories\AssociationTypeFieldRepository;
use Webkul\Product\Repositories\AssociationTypeRepository;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('lists association types including the seeded defaults', function () {
    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.association-types.index'))
        ->assertOk();

    $codes = collect($response->json('data'))->pluck('code');

    expect($codes)->toContain('related_products', 'up_sells', 'cross_sells');
});

it('gets a single association type by code', function () {
    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.association-types.get', ['code' => 'related_products']))
        ->assertOk();

    expect($response->json('code'))->toBe('related_products')
        ->and($response->json('is_user_defined'))->toBeFalse();
});

it('returns a 404 for an unknown association type code', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.association-types.get', ['code' => 'does-not-exist']))
        ->assertStatus(404);
});

it('creates a new association type', function () {
    $code = 'bundle_'.Str::random(8);

    $response = $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.association-types.store'), [
            'code'   => $code,
            'status' => true,
            'en_US'  => ['name' => 'Bundle'],
        ])
        ->assertStatus(201);

    expect($response->json('success'))->toBeTrue();

    $associationType = app(AssociationTypeRepository::class)->findByCode($code);

    expect($associationType)->not->toBeNull()
        ->and((bool) $associationType->is_user_defined)->toBeTrue()
        ->and((bool) $associationType->status)->toBeTrue();
});

it('rejects creating an association type with a duplicate code', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.association-types.store'), [
            'code' => 'related_products',
        ])
        ->assertStatus(422);
});

it('rejects creating an association type with a reserved code', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.association-types.store'), [
            'code' => 'type',
        ])
        ->assertStatus(422);
});

it('updates an association type\'s status and label', function () {
    $code = 'bundle_'.Str::random(8);

    app(AssociationTypeRepository::class)->create([
        'code'            => $code,
        'status'          => true,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Original Name'],
    ]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.association-types.update', ['code' => $code]), [
            'status' => false,
            'en_US'  => ['name' => 'Updated Name'],
        ])
        ->assertOk();

    $associationType = app(AssociationTypeRepository::class)->findByCode($code)->fresh();

    expect((bool) $associationType->status)->toBeFalse()
        ->and($associationType->translate('en_US')->name)->toBe('Updated Name');
});

it('rejects updating the immutable code and is_user_defined fields', function () {
    $code = 'bundle_'.Str::random(8);

    app(AssociationTypeRepository::class)->create([
        'code'            => $code,
        'status'          => true,
        'is_user_defined' => 1,
    ]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.association-types.update', ['code' => $code]), [
            'code'            => 'renamed',
            'is_user_defined' => 0,
        ])
        ->assertStatus(422);

    expect(app(AssociationTypeRepository::class)->findByCode($code))->not->toBeNull()
        ->and(app(AssociationTypeRepository::class)->findByCode('renamed'))->toBeNull();
});

it('deletes a user-defined association type', function () {
    $code = 'bundle_'.Str::random(8);

    app(AssociationTypeRepository::class)->create([
        'code'            => $code,
        'status'          => true,
        'is_user_defined' => 1,
    ]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.association-types.delete', ['code' => $code]))
        ->assertOk();

    expect(app(AssociationTypeRepository::class)->findByCode($code))->toBeNull();
});

it('refuses to delete a default (non-user-defined) association type', function () {
    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.association-types.delete', ['code' => 'related_products']))
        ->assertStatus(422);

    expect(app(AssociationTypeRepository::class)->findByCode('related_products'))->not->toBeNull();
});

describe('association type custom fields', function () {
    function createUserDefinedAssociationTypeForFieldTests(): string
    {
        $code = 'bundle_'.Str::random(8);

        app(AssociationTypeRepository::class)->create([
            'code'            => $code,
            'status'          => true,
            'is_user_defined' => 1,
        ]);

        return $code;
    }

    it('lists the custom fields defined on an association type', function () {
        $typeCode = createUserDefinedAssociationTypeForFieldTests();
        $associationType = app(AssociationTypeRepository::class)->findByCode($typeCode);

        app(AssociationTypeFieldRepository::class)->create([
            'association_type_id' => $associationType->id,
            'code'                => 'quantity',
            'type'                => 'text',
            'validation'          => 'number',
            'is_required'         => true,
        ]);

        $response = $this->withHeaders($this->headers)
            ->json('GET', route('admin.api.association-types-fields.get', ['code' => $typeCode]))
            ->assertOk();

        $fields = collect($response->json());

        expect($fields->pluck('code'))->toContain('quantity')
            ->and($fields->firstWhere('code', 'quantity')['validation'])->toBe('number')
            ->and($fields->firstWhere('code', 'quantity')['is_required'])->toBe(1);
    });

    it('creates a custom field on an association type, matching what rich-associations write validation expects', function () {
        $typeCode = createUserDefinedAssociationTypeForFieldTests();

        $this->withHeaders($this->headers)
            ->json('POST', route('admin.api.association-types-fields.store', ['code' => $typeCode]), [
                'code'        => 'quantity',
                'type'        => 'text',
                'validation'  => 'number',
                'is_required' => true,
                'en_US'       => ['name' => 'Quantity'],
            ])
            ->assertStatus(201);

        $associationType = app(AssociationTypeRepository::class)->findByCode($typeCode);
        $field = $associationType->fields()->where('code', 'quantity')->first();

        expect($field)->not->toBeNull()
            ->and($field->type)->toBe('text')
            ->and($field->validation)->toBe('number')
            ->and((bool) $field->is_required)->toBeTrue();
    });

    it('rejects creating a field with a type outside the configured association field types', function () {
        $typeCode = createUserDefinedAssociationTypeForFieldTests();

        $this->withHeaders($this->headers)
            ->json('POST', route('admin.api.association-types-fields.store', ['code' => $typeCode]), [
                'code' => 'quantity',
                'type' => 'select',
            ])
            ->assertStatus(422);
    });

    it('rejects creating a field with a code already used on the same association type', function () {
        $typeCode = createUserDefinedAssociationTypeForFieldTests();
        $associationType = app(AssociationTypeRepository::class)->findByCode($typeCode);

        app(AssociationTypeFieldRepository::class)->create([
            'association_type_id' => $associationType->id,
            'code'                => 'quantity',
            'type'                => 'text',
        ]);

        $this->withHeaders($this->headers)
            ->json('POST', route('admin.api.association-types-fields.store', ['code' => $typeCode]), [
                'code' => 'quantity',
                'type' => 'boolean',
            ])
            ->assertStatus(422);
    });

    it('allows the same field code to be reused across two different association types', function () {
        $firstTypeCode = createUserDefinedAssociationTypeForFieldTests();
        $secondTypeCode = createUserDefinedAssociationTypeForFieldTests();

        $firstType = app(AssociationTypeRepository::class)->findByCode($firstTypeCode);

        app(AssociationTypeFieldRepository::class)->create([
            'association_type_id' => $firstType->id,
            'code'                => 'quantity',
            'type'                => 'text',
        ]);

        $this->withHeaders($this->headers)
            ->json('POST', route('admin.api.association-types-fields.store', ['code' => $secondTypeCode]), [
                'code' => 'quantity',
                'type' => 'text',
            ])
            ->assertStatus(201);
    });

    it('updates a custom field\'s validation and label', function () {
        $typeCode = createUserDefinedAssociationTypeForFieldTests();
        $associationType = app(AssociationTypeRepository::class)->findByCode($typeCode);

        app(AssociationTypeFieldRepository::class)->create([
            'association_type_id' => $associationType->id,
            'code'                => 'quantity',
            'type'                => 'text',
        ]);

        $this->withHeaders($this->headers)
            ->json('PUT', route('admin.api.association-types-fields.update', ['code' => $typeCode, 'fieldCode' => 'quantity']), [
                'is_required' => true,
                'en_US'       => ['name' => 'Required Quantity'],
            ])
            ->assertOk();

        $field = $associationType->fields()->where('code', 'quantity')->first()->fresh();

        expect((bool) $field->is_required)->toBeTrue()
            ->and($field->translate('en_US')->name)->toBe('Required Quantity');
    });

    it('rejects updating a field\'s immutable code and type', function () {
        $typeCode = createUserDefinedAssociationTypeForFieldTests();
        $associationType = app(AssociationTypeRepository::class)->findByCode($typeCode);

        app(AssociationTypeFieldRepository::class)->create([
            'association_type_id' => $associationType->id,
            'code'                => 'quantity',
            'type'                => 'text',
        ]);

        $this->withHeaders($this->headers)
            ->json('PUT', route('admin.api.association-types-fields.update', ['code' => $typeCode, 'fieldCode' => 'quantity']), [
                'code' => 'renamed',
                'type' => 'boolean',
            ])
            ->assertStatus(422);

        $field = $associationType->fields()->where('code', 'quantity')->first();

        expect($field)->not->toBeNull()
            ->and($field->type)->toBe('text');
    });

    it('returns a 404 when updating a field that does not exist on the association type', function () {
        $typeCode = createUserDefinedAssociationTypeForFieldTests();

        $this->withHeaders($this->headers)
            ->json('PUT', route('admin.api.association-types-fields.update', ['code' => $typeCode, 'fieldCode' => 'does-not-exist']), [
                'is_required' => true,
            ])
            ->assertStatus(404);
    });

    it('deletes a custom field from an association type', function () {
        $typeCode = createUserDefinedAssociationTypeForFieldTests();
        $associationType = app(AssociationTypeRepository::class)->findByCode($typeCode);

        app(AssociationTypeFieldRepository::class)->create([
            'association_type_id' => $associationType->id,
            'code'                => 'quantity',
            'type'                => 'text',
        ]);

        $this->withHeaders($this->headers)
            ->json('DELETE', route('admin.api.association-types-fields.delete', ['code' => $typeCode, 'fieldCode' => 'quantity']))
            ->assertOk();

        expect($associationType->fields()->where('code', 'quantity')->first())->toBeNull();
    });
});

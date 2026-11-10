<?php

use Illuminate\Support\Str;
use Webkul\AdminApi\Http\Middleware\LocaleMiddleware;
use Webkul\Product\Repositories\AssociationTypeRepository;

/**
 * @param  array<int, string>  $permissions
 */
function headersWithAssociationTypePermissions(array $permissions): array
{
    return test()->getAuthenticationHeaders('custom', ['api.catalog.association_types', ...$permissions]);
}

function associationTypeWithQuantityField(): array
{
    $code = 'bundle_'.Str::random(8);

    $associationType = app(AssociationTypeRepository::class)->create([
        'code'            => $code,
        'is_user_defined' => 1,
        'fields'          => [['code' => 'quantity', 'type' => 'text', 'is_required' => 0]],
    ]);

    return [$code, $associationType->fields()->where('code', 'quantity')->first()];
}

it('rejects nested fields on create without the field create permission', function (): void {
    $code = 'bundle_'.Str::random(8);

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.create']))
        ->json('POST', route('admin.api.association-types.store'), [
            'code'   => $code,
            'fields' => [['code' => 'quantity', 'type' => 'text']],
        ])
        ->assertForbidden();

    expect(app(AssociationTypeRepository::class)->findByCode($code))->toBeNull();
});

it('still creates an association type without nested fields under the type permission alone', function (): void {
    $code = 'bundle_'.Str::random(8);

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.create']))
        ->json('POST', route('admin.api.association-types.store'), ['code' => $code])
        ->assertCreated();

    expect(app(AssociationTypeRepository::class)->findByCode($code))->not->toBeNull();
});

it('creates nested fields with the field create permission', function (): void {
    $code = 'bundle_'.Str::random(8);

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.create', 'api.catalog.association_types.fields.create']))
        ->json('POST', route('admin.api.association-types.store'), [
            'code'   => $code,
            'fields' => [['code' => 'quantity', 'type' => 'text']],
        ])
        ->assertCreated();

    expect(app(AssociationTypeRepository::class)->findByCode($code)->fields()->pluck('code')->all())->toBe(['quantity']);
});

it('rejects nested field writes on update without the matching field permission', function (string $write): void {
    [$code, $field] = associationTypeWithQuantityField();

    $fields = match ($write) {
        'edit by id'   => [$field->id => ['code' => 'quantity', 'is_required' => 1]],
        'edit by code' => ['new_1' => ['code' => 'quantity', 'isNew' => 'true', 'is_required' => 1]],
        'delete'       => [$field->id => ['code' => 'quantity', 'isDelete' => 'true']],
        'create'       => ['new_1' => ['code' => 'weight', 'type' => 'text', 'isNew' => 'true']],
    };

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.edit']))
        ->json('PUT', route('admin.api.association-types.update', ['code' => $code]), ['fields' => $fields])
        ->assertForbidden();

    $associationType = app(AssociationTypeRepository::class)->findByCode($code);

    expect($associationType->fields()->pluck('code')->all())->toBe(['quantity'])
        ->and((bool) $field->fresh()->is_required)->toBeFalse();
})->with(['edit by id', 'edit by code', 'delete', 'create']);

it('applies each nested field write only with its own permission on update', function (): void {
    [$code, $field] = associationTypeWithQuantityField();

    $headers = headersWithAssociationTypePermissions(['api.catalog.association_types.edit', 'api.catalog.association_types.fields.edit']);

    $this->withHeaders($headers)
        ->json('PUT', route('admin.api.association-types.update', ['code' => $code]), [
            'fields' => [$field->id => ['code' => 'quantity', 'is_required' => 1]],
        ])
        ->assertOk();

    $this->withHeaders($headers)
        ->json('PUT', route('admin.api.association-types.update', ['code' => $code]), [
            'fields' => [$field->id => ['code' => 'quantity', 'isDelete' => 'true']],
        ])
        ->assertForbidden();

    expect((bool) $field->fresh()->is_required)->toBeTrue()
        ->and($field->fresh())->not->toBeNull();
});

it('still updates an association type without nested fields under the type permission alone', function (): void {
    [$code] = associationTypeWithQuantityField();

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.edit']))
        ->json('PUT', route('admin.api.association-types.update', ['code' => $code]), ['status' => false])
        ->assertOk();

    expect((bool) app(AssociationTypeRepository::class)->findByCode($code)->status)->toBeFalse();
});

it('refuses nested fields in the labels envelope as an unknown locale', function (): void {
    $code = 'bundle_'.Str::random(8);

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.create']))
        ->json('POST', route('admin.api.association-types.store'), [
            'code'   => $code,
            'labels' => ['fields' => ['code' => 'quantity', 'type' => 'text']],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('locale');

    expect(app(AssociationTypeRepository::class)->findByCode($code))->toBeNull();
});

it('checks nested fields from the labels envelope even without the locale middleware', function (): void {
    $this->withoutMiddleware(LocaleMiddleware::class);

    $code = 'bundle_'.Str::random(8);

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.create']))
        ->json('POST', route('admin.api.association-types.store'), [
            'code'   => $code,
            'labels' => ['fields' => ['code' => 'quantity', 'type' => 'text']],
        ])
        ->assertForbidden();

    expect(app(AssociationTypeRepository::class)->findByCode($code))->toBeNull();

    [$existingCode, $field] = associationTypeWithQuantityField();

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.edit']))
        ->json('PUT', route('admin.api.association-types.update', ['code' => $existingCode]), [
            'labels' => ['fields' => ['code' => 'quantity', 'isDelete' => 'true']],
        ])
        ->assertForbidden();

    expect($field->fresh())->not->toBeNull();
});

it('treats a non-canonical id key as an edit of the field it resolves to', function (): void {
    [$code, $field] = associationTypeWithQuantityField();

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.edit', 'api.catalog.association_types.fields.create']))
        ->json('PUT', route('admin.api.association-types.update', ['code' => $code]), [
            'fields' => ['0'.$field->id => ['is_required' => 1]],
        ])
        ->assertForbidden();

    expect((bool) $field->fresh()->is_required)->toBeFalse();
});

it('refuses a nested entry that names another association type', function (): void {
    [$code, $field] = associationTypeWithQuantityField();

    [, $otherField] = associationTypeWithQuantityField();

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.edit', 'api.catalog.association_types.fields.edit']))
        ->json('PUT', route('admin.api.association-types.update', ['code' => $code]), [
            'fields' => [$field->id => ['code' => 'quantity', 'association_type_id' => $otherField->association_type_id]],
        ])
        ->assertForbidden();

    expect($field->fresh()->association_type_id)->not->toBe($otherField->association_type_id);
});

it('accepts a nested entry that names its own association type', function (): void {
    [$code, $field] = associationTypeWithQuantityField();

    $this->withHeaders(headersWithAssociationTypePermissions(['api.catalog.association_types.edit', 'api.catalog.association_types.fields.edit']))
        ->json('PUT', route('admin.api.association-types.update', ['code' => $code]), [
            'fields' => [$field->id => ['code' => 'quantity', 'is_required' => 1, 'association_type_id' => $field->association_type_id]],
        ])
        ->assertOk();

    expect((bool) $field->fresh()->is_required)->toBeTrue();
});

<?php

use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\ProductPassport\Models\PassportTemplate;

beforeEach(function (): void {
    $this->setPassportConfig(['enabled' => '1']);
});

it('lists templates for an admin with the view permission', function (): void {
    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.passports.templates.index'))->assertOk();
});

it('forbids the template screen without the permission', function (): void {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->get(route('admin.catalog.passports.templates.index'))->assertForbidden();
});

it('renders the create modal on the listing', function (): void {
    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.passports.templates.index'))
        ->assertOk()
        ->assertSee('v-passport-template-create', false);
});

it('creates a template from the create modal', function (): void {
    $this->loginWithPermissions('all');

    $this->post(route('admin.catalog.passports.templates.store'), [
        'code'    => 'espr_general',
        'en_US'   => ['name' => 'EU ESPR (general)'],
    ])->assertRedirect();

    $template = PassportTemplate::query()->where('code', 'espr_general')->first();

    expect($template)->not->toBeNull()
        ->and($template->translate('en_US')->name)->toBe('EU ESPR (general)');
});

it('renders the editor with the saved sections and fields', function (): void {
    $this->loginWithPermissions('all');

    $attribute = AttributeProxy::factory()->create(['code' => 'carbon_footprint', 'type' => 'text']);

    $template = PassportTemplate::create(['code' => 'espr_general', 'is_enabled' => true]);

    $section = $template->sections()->create(['code' => 'circularity', 'position' => 0, 'en_US' => ['name' => 'Circularity']]);

    $template->fields()->create([
        'code'                         => 'carbon',
        'passport_template_section_id' => $section->id,
        'source_type'                  => 'attribute',
        'attribute_id'                 => $attribute->id,
        'tier'                         => 'consumer',
        'is_required'                  => true,
        'position'                     => 0,
        'en_US'                        => ['label' => 'Carbon Footprint'],
    ]);

    $this->get(route('admin.catalog.passports.templates.edit', $template->id))
        ->assertOk()
        ->assertSee('espr_general')
        ->assertSee('Carbon Footprint', false)
        ->assertSee('Circularity', false);
});

it('renders every translatable field with a single values binding', function (): void {
    $this->loginWithPermissions('all');

    $template = PassportTemplate::create(['code' => 'espr_general', 'is_enabled' => true]);

    $html = $this->get(route('admin.catalog.passports.templates.edit', $template->id))
        ->assertOk()
        ->getContent();

    preg_match_all('/<v-translatable-field[^>]*>/s', $html, $matches);

    expect($matches[0])->not->toBeEmpty();

    foreach ($matches[0] as $tag) {
        expect(preg_match_all('/\s:values=/', $tag))->toBe(1);
    }
});

it('saves families, sections and fields as one payload', function (): void {
    $this->loginWithPermissions('all');

    $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();

    $attribute = AttributeProxy::factory()->create(['code' => 'carbon_footprint', 'type' => 'text']);

    $template = PassportTemplate::create(['code' => 'espr_general', 'is_enabled' => true]);

    $this->put(route('admin.catalog.passports.templates.update', $template->id), [
        'code'       => 'espr_general',
        'is_enabled' => 1,
        'en_US'      => ['name' => 'EU ESPR (general)'],
        'families'   => [$family->id],
        'sections'   => [
            ['code' => 'circularity', 'en_US' => ['name' => 'Circularity']],
        ],
        'fields' => [
            [
                'code'         => 'carbon',
                'section'      => 'circularity',
                'source_type'  => 'attribute',
                'attribute_id' => $attribute->id,
                'tier'         => 'consumer',
                'is_required'  => 1,
                'role'         => '',
                'en_US'        => ['label' => 'Carbon Footprint'],
            ], [
                'code'        => 'takeback',
                'section'     => 'circularity',
                'source_type' => 'fixed',
                'tier'        => 'operator',
                'is_required' => 0,
                'role'        => '',
                'en_US'       => ['label' => 'Take-back Scheme', 'fixed_value' => 'Return in store'],
            ],
        ],
    ])->assertOk();

    $template->refresh();

    expect($template->families->pluck('id')->all())->toBe([$family->id])
        ->and($template->sections)->toHaveCount(1)
        ->and($template->fields)->toHaveCount(2);

    $carbon = $template->fields->firstWhere('code', 'carbon');

    expect($carbon->attribute_id)->toBe($attribute->id)
        ->and($carbon->is_required)->toBeTrue()
        ->and($carbon->section->code)->toBe('circularity')
        ->and($carbon->translate('en_US')->label)->toBe('Carbon Footprint');

    $takeback = $template->fields->firstWhere('code', 'takeback');

    expect($takeback->attribute_id)->toBeNull()
        ->and($takeback->tier->value)->toBe('operator')
        ->and($takeback->translate('en_US')->fixed_value)->toBe('Return in store');
});

it('drops rows that the editor removed from the payload', function (): void {
    $this->loginWithPermissions('all');

    $attribute = AttributeProxy::factory()->create(['code' => 'carbon_footprint', 'type' => 'text']);

    $template = PassportTemplate::create(['code' => 'espr_general', 'is_enabled' => true]);

    $payload = fn (array $fields): array => [
        'code'   => 'espr_general',
        'en_US'  => ['name' => 'EU ESPR (general)'],
        'fields' => $fields,
    ];

    $field = fn (string $code): array => [
        'code'         => $code,
        'source_type'  => 'attribute',
        'attribute_id' => $attribute->id,
        'tier'         => 'consumer',
        'en_US'        => ['label' => ucfirst($code)],
    ];

    $this->put(route('admin.catalog.passports.templates.update', $template->id), $payload([
        $field('carbon'),
        $field('recycled'),
    ]))->assertOk();

    $this->put(route('admin.catalog.passports.templates.update', $template->id), $payload([
        $field('carbon'),
    ]))->assertOk();

    expect($template->refresh()->fields->pluck('code')->all())->toBe(['carbon']);
});

it('rejects an attribute-sourced field with no attribute', function (): void {
    $this->loginWithPermissions('all');

    $template = PassportTemplate::create(['code' => 'espr_general', 'is_enabled' => true]);

    $this->put(route('admin.catalog.passports.templates.update', $template->id), [
        'code'   => 'espr_general',
        'fields' => [
            [
                'code'        => 'carbon',
                'source_type' => 'attribute',
                'tier'        => 'consumer',
                'en_US'       => ['label' => 'Carbon Footprint'],
            ],
        ],
    ])->assertSessionHasErrors('fields.0.attribute_id');

    expect($template->refresh()->fields)->toHaveCount(0);
});

it('rejects two fields claiming the same identifier role', function (): void {
    $this->loginWithPermissions('all');

    $attribute = AttributeProxy::factory()->create(['code' => 'ean', 'type' => 'text']);

    $template = PassportTemplate::create(['code' => 'espr_general', 'is_enabled' => true]);

    $this->put(route('admin.catalog.passports.templates.update', $template->id), [
        'code'   => 'espr_general',
        'fields' => [
            [
                'code'         => 'ean',
                'source_type'  => 'attribute',
                'attribute_id' => $attribute->id,
                'tier'         => 'consumer',
                'role'         => 'gtin',
                'en_US'        => ['label' => 'EAN'],
            ], [
                'code'         => 'ean_alt',
                'source_type'  => 'attribute',
                'attribute_id' => $attribute->id,
                'tier'         => 'consumer',
                'role'         => 'gtin',
                'en_US'        => ['label' => 'EAN alternate'],
            ],
        ],
    ])->assertSessionHasErrors('fields.1.role');
});

it('rejects a family already claimed by another template', function (): void {
    $this->loginWithPermissions('all');

    $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();

    $owner = PassportTemplate::create(['code' => 'battery', 'is_enabled' => true]);
    $owner->families()->attach($family->id);

    $other = PassportTemplate::create(['code' => 'espr_general', 'is_enabled' => true]);

    $this->put(route('admin.catalog.passports.templates.update', $other->id), [
        'code'     => 'espr_general',
        'families' => [$family->id],
    ])->assertSessionHasErrors('families');

    expect($other->refresh()->families)->toHaveCount(0);
});

it('keeps the code immutable on update', function (): void {
    $this->loginWithPermissions('all');

    $template = PassportTemplate::create(['code' => 'espr_general', 'is_enabled' => true]);

    $this->put(route('admin.catalog.passports.templates.update', $template->id), [
        'code'  => 'renamed_code',
        'en_US' => ['name' => 'EU ESPR (general)'],
    ])->assertOk();

    expect($template->refresh()->code)->toBe('espr_general');
});

it('deletes a template with the delete permission', function (): void {
    $this->loginWithPermissions('all');

    $template = PassportTemplate::create(['code' => 'espr_general', 'is_enabled' => true]);

    $this->delete(route('admin.catalog.passports.templates.delete', $template->id))->assertOk();

    expect(PassportTemplate::query()->whereKey($template->id)->exists())->toBeFalse();
});

it('offers only attributes of the bound families as sources', function (): void {
    $this->loginWithPermissions('all');

    $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();

    $outsider = AttributeProxy::factory()->create(['code' => 'not_in_family', 'type' => 'text']);

    $inFamily = $family->attributeFamilyGroupMappings()->first()?->customAttributes()->first();

    $codes = array_column(
        $this->getJson(route('admin.catalog.options.fetch-all', [
            'entityName' => 'attributes',
            'inFamilies' => [$family->id],
            'perPage'    => 5000,
        ]))->assertOk()->json('options'),
        'code'
    );

    expect($codes)->toContain($inFamily->code)
        ->and($codes)->not->toContain($outsider->code);
});

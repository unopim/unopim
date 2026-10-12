<?php

use Webkul\Attribute\Models\AttributeProxy;
use Webkul\ProductPassport\Models\PassportTemplate;

/**
 * Removing a section in the builder has to carry its fields to the default
 * section. A field left pointing at the removed code is rejected as an unknown
 * section, and the whole save is lost — including the removal itself.
 */
beforeEach(function (): void {
    $this->setPassportConfig(['enabled' => '1']);

    $this->loginWithPermissions('all');

    $this->attribute = AttributeProxy::factory()->create(['code' => 'battery_sku', 'type' => 'text']);

    $this->template = PassportTemplate::create(['code' => 'tpl_battery', 'is_enabled' => true]);

    $this->payload = fn (array $sections, string $section): array => [
        'code'       => 'tpl_battery',
        'is_enabled' => 1,
        'en_US'      => ['name' => 'EU Battery Passport'],
        'sections'   => $sections,
        'fields'     => [
            [
                'code'         => 'battery_unique_id',
                'section'      => $section,
                'source_type'  => 'attribute',
                'attribute_id' => $this->attribute->id,
                'tier'         => 'consumer',
                'is_required'  => 1,
                'role'         => '',
                'en_US'        => ['label' => 'Unique Battery Identifier'],
            ],
        ],
    ];

    $this->put(
        route('admin.catalog.passports.templates.update', $this->template->id),
        ($this->payload)([['code' => 'battery', 'en_US' => ['name' => 'Battery Identity']]], 'battery')
    )->assertOk();
});

it('removes the section and keeps the field when the field is moved to the default section', function (): void {
    $this->put(
        route('admin.catalog.passports.templates.update', $this->template->id),
        ($this->payload)([], '')
    )->assertOk();

    $this->template->refresh();

    expect($this->template->sections)->toHaveCount(0)
        ->and($this->template->fields)->toHaveCount(1);

    $field = $this->template->fields->firstWhere('code', 'battery_unique_id');

    expect($field->passport_template_section_id)->toBeNull()
        ->and($field->attribute_id)->toBe($this->attribute->id);
});

it('rejects the save when a field still points at the removed section', function (): void {
    $this->put(
        route('admin.catalog.passports.templates.update', $this->template->id),
        ($this->payload)([], 'battery')
    )->assertSessionHasErrors('fields.0.section');

    $this->template->refresh();

    expect($this->template->sections)->toHaveCount(1);
});

it('keeps fields that belong to the sections left standing', function (): void {
    $this->put(
        route('admin.catalog.passports.templates.update', $this->template->id),
        ($this->payload)([
            ['code' => 'battery', 'en_US' => ['name' => 'Battery Identity']],
            ['code' => 'performance', 'en_US' => ['name' => 'Performance']],
        ], 'performance')
    )->assertOk();

    $this->template->refresh();

    expect($this->template->sections)->toHaveCount(2)
        ->and($this->template->fields->firstWhere('code', 'battery_unique_id')->section->code)->toBe('performance');
});

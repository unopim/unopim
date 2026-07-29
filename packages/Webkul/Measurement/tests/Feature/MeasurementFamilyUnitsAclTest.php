<?php

use Webkul\Measurement\Models\MeasurementFamily;

function measurementUnitsAclFamily(): MeasurementFamily
{
    return MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'symbol'        => 'm',
        'units'         => [
            [
                'code'                  => 'meter',
                'labels'                => ['en_US' => 'Meter'],
                'symbol'                => 'm',
                'convert_from_standard' => [['operator' => 'mul', 'value' => '1']],
            ],
            [
                'code'                  => 'centimeter',
                'labels'                => ['en_US' => 'Centimeter'],
                'symbol'                => 'cm',
                'convert_from_standard' => [['operator' => 'mul', 'value' => '0.01']],
            ],
        ],
    ]);
}

function measurementUnitsAclBasePermissions(): array
{
    return [
        'dashboard',
        'catalog',
        'catalog.measurements',
        'catalog.measurements.families',
        'catalog.measurements.families.edit',
    ];
}

function measurementUnitsAclVisibleMarkup(string $html): string
{
    return (string) preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
}

function measurementUnitsAclInertTemplate(string $html): string
{
    preg_match('#<script type="text/x-template" id="v-locales-template">(.*?)</script>#s', $html, $matches);

    return $matches[1] ?? '';
}

describe('measurement family edit page units card visibility', function () {
    it('hides the heading, create button and datagrid from a role without the units permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: measurementUnitsAclBasePermissions());

        $response = $this->get(route('admin.measurement.families.edit', $family->id));

        $response->assertOk();

        $visible = measurementUnitsAclVisibleMarkup($response->getContent());

        expect($visible)->not->toContain('<v-locales>')
            ->and($visible)->not->toContain(trans('measurement::app.measurement.edit.units'))
            ->and($visible)->not->toContain(trans('measurement::app.measurement.edit.create_units'))
            ->and($visible)->not->toContain('<v-datagrid')
            ->and($visible)->not->toContain(route('admin.measurement.families.units', $family->id));
    });

    it('shows the heading and the units datagrid for a role holding only the units view permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: [
            ...measurementUnitsAclBasePermissions(),
            'catalog.measurements.units',
        ]);

        $response = $this->get(route('admin.measurement.families.edit', $family->id));

        $response->assertOk();

        $content = $response->getContent();
        $visible = measurementUnitsAclVisibleMarkup($content);
        $template = measurementUnitsAclInertTemplate($content);

        expect($visible)->toContain('<v-locales>')
            ->and($visible)->toContain('</v-locales>')
            ->and($visible)->toContain(trans('measurement::app.measurement.edit.units'))
            ->and($template)->toContain('src="'.route('admin.measurement.families.units', $family->id).'"');

        expect($content)->not->toContain(trans('measurement::app.measurement.edit.create_units'))
            ->and($content)->not->toContain("a.index === 'edit'")
            ->and($content)->not->toContain("a.index === 'delete'")
            ->and($content)->not->toContain(trans('measurement::app.measurement.unit.conversion_operation'));
    });

    it('shows the create units button once the units create permission is granted', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: [
            ...measurementUnitsAclBasePermissions(),
            'catalog.measurements.units',
            'catalog.measurements.units.create',
        ]);

        $response = $this->get(route('admin.measurement.families.edit', $family->id));

        $response->assertOk();

        $content = $response->getContent();
        $visible = measurementUnitsAclVisibleMarkup($content);

        expect($visible)->toContain('<v-locales>')
            ->and($visible)->toContain(trans('measurement::app.measurement.edit.units'))
            ->and($visible)->toContain(trans('measurement::app.measurement.edit.create_units'));

        expect($content)->toContain(trans('measurement::app.measurement.unit.conversion_operation'))
            ->and($content)->not->toContain("a.index === 'edit'")
            ->and($content)->not->toContain("a.index === 'delete'");
    });

    it('renders every units control for a role with full permissions', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions('all', ['dashboard']);

        $response = $this->get(route('admin.measurement.families.edit', $family->id));

        $response->assertOk();

        $content = $response->getContent();
        $visible = measurementUnitsAclVisibleMarkup($content);
        $template = measurementUnitsAclInertTemplate($content);

        expect($visible)->toContain('<v-locales>')
            ->and($visible)->toContain(trans('measurement::app.measurement.edit.units'))
            ->and($visible)->toContain(trans('measurement::app.measurement.edit.create_units'));

        expect($template)->toContain('src="'.route('admin.measurement.families.units', $family->id).'"')
            ->and($template)->toContain("a.index === 'edit'")
            ->and($template)->toContain("a.index === 'delete'")
            ->and($template)->toContain(trans('measurement::app.measurement.unit.conversion_operation'));
    });

    it('renders the whole page without a blade nesting error for any units permission set', function (array $extraPermissions) {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: [
            ...measurementUnitsAclBasePermissions(),
            ...$extraPermissions,
        ]);

        $response = $this->get(route('admin.measurement.families.edit', $family->id));

        $response->assertOk();

        $content = $response->getContent();

        expect($content)->toContain(trans('measurement::app.measurement.edit.measurement_edit'))
            ->and($content)->toContain(trans('measurement::app.measurement.edit.general'))
            ->and(substr_count($content, '<v-locales>'))->toBe(substr_count($content, '</v-locales>'))
            ->and(rtrim($content))->toEndWith('</html>');
    })->with([
        'no units permissions'  => [[]],
        'units view only'       => [['catalog.measurements.units']],
        'units view + create'   => [['catalog.measurements.units', 'catalog.measurements.units.create']],
        'units view + edit'     => [['catalog.measurements.units', 'catalog.measurements.units.edit']],
        'units view + delete'   => [['catalog.measurements.units', 'catalog.measurements.units.delete']],
        'all units permissions' => [[
            'catalog.measurements.units',
            'catalog.measurements.units.create',
            'catalog.measurements.units.edit',
            'catalog.measurements.units.delete',
        ]],
        'create without view'   => [['catalog.measurements.units.create']],
        'edit without view'     => [['catalog.measurements.units.edit']],
        'delete without view'   => [['catalog.measurements.units.delete']],
    ]);

    it('keeps the units card hidden when only the write permissions are granted without the view permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: [
            ...measurementUnitsAclBasePermissions(),
            'catalog.measurements.units.create',
            'catalog.measurements.units.edit',
            'catalog.measurements.units.delete',
        ]);

        $response = $this->get(route('admin.measurement.families.edit', $family->id));

        $response->assertOk();

        $visible = measurementUnitsAclVisibleMarkup($response->getContent());

        expect($visible)->not->toContain('<v-locales>')
            ->and($visible)->not->toContain(trans('measurement::app.measurement.edit.units'))
            ->and($visible)->not->toContain(trans('measurement::app.measurement.edit.create_units'));
    });
});

describe('measurement family units backend enforcement', function () {
    it('forbids the units datagrid xhr for a role without the units permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: measurementUnitsAclBasePermissions());

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.measurement.families.units', $family->id));

        $response->assertForbidden();

        expect($response->getContent())->not->toContain('centimeter');
    });

    it('returns the units datagrid payload for a role holding the units permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: [
            ...measurementUnitsAclBasePermissions(),
            'catalog.measurements.units',
        ]);

        $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.measurement.families.units', $family->id));

        $response->assertOk();

        $payload = $response->json();

        expect($payload)->toHaveKeys(['records', 'columns'])
            ->and($payload['records'])->not->toBeEmpty();
    });

    it('forbids storing a unit without the units create permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: [
            ...measurementUnitsAclBasePermissions(),
            'catalog.measurements.units',
        ]);

        $this->post(route('admin.measurement.families.units.store', $family->id), [
            'code'                  => 'kilometer',
            'labels'                => ['en_US' => 'Kilometer'],
            'symbol'                => 'km',
            'convert_from_standard' => ['mul'],
            'convert_value'         => ['1000'],
        ])->assertForbidden();

        expect(MeasurementFamily::find($family->id)->units)->toHaveCount(2);
    });

    it('forbids reading and updating a unit without the units edit permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: [
            ...measurementUnitsAclBasePermissions(),
            'catalog.measurements.units',
            'catalog.measurements.units.create',
        ]);

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.measurement.families.units.edit', [
                'familyId' => $family->id,
                'code'     => 'centimeter',
            ]))
            ->assertForbidden();

        $this->put(route('admin.measurement.families.units.update', [
            'familyId' => $family->id,
            'code'     => 'centimeter',
        ]), [
            'code'                  => 'centimeter',
            'labels'                => ['en_US' => 'Hacked'],
            'symbol'                => 'cm',
            'convert_from_standard' => ['mul'],
            'convert_value'         => ['0.01'],
        ])->assertForbidden();

        $labels = collect(MeasurementFamily::find($family->id)->units)
            ->firstWhere('code', 'centimeter')['labels'];

        expect($labels['en_US'])->toBe('Centimeter');
    });

    it('forbids deleting a unit without the units delete permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: [
            ...measurementUnitsAclBasePermissions(),
            'catalog.measurements.units',
            'catalog.measurements.units.create',
            'catalog.measurements.units.edit',
        ]);

        $this->delete(route('admin.measurement.families.units.delete', [
            'familyId' => $family->id,
            'code'     => 'centimeter',
        ]))->assertForbidden();

        expect(MeasurementFamily::find($family->id)->units)->toHaveCount(2);
    });

    it('forbids the non ajax units page for a role without the units permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: measurementUnitsAclBasePermissions());

        $this->get(route('admin.measurement.families.units', $family->id))
            ->assertForbidden();
    });
});

describe('measurement family units pushed script template', function () {
    it('does not ship the units template for a role without the units permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: measurementUnitsAclBasePermissions());

        $response = $this->get(route('admin.measurement.families.edit', $family->id));

        $response->assertOk();

        $content = $response->getContent();
        $visible = measurementUnitsAclVisibleMarkup($content);

        $datagridSource = route('admin.measurement.families.units', $family->id);

        expect($content)->not->toContain('v-locales-template')
            ->and($content)->not->toContain($datagridSource)
            ->and($content)->not->toContain("app.component('v-locales'");

        expect($visible)->not->toContain('<v-datagrid')
            ->and($visible)->not->toContain('<v-locales>');

        expect(substr_count($content, '<v-locales>'))->toBe(0);
    });

    it('ships the units template for a role holding the units permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: [...measurementUnitsAclBasePermissions(), 'catalog.measurements.units']);

        $response = $this->get(route('admin.measurement.families.edit', $family->id));

        $response->assertOk();

        $content = $response->getContent();
        $template = measurementUnitsAclInertTemplate($content);

        $datagridSource = route('admin.measurement.families.units', $family->id);

        expect($content)->toContain('v-locales-template')
            ->and($content)->toContain("app.component('v-locales'");

        expect($template)->toContain('src="'.$datagridSource.'"');

        expect(substr_count($content, '<v-locales>'))->toBe(1);
    });

    it('does not ship the create or edit modal markup for a role without the units permission', function () {
        $family = measurementUnitsAclFamily();

        $this->loginWithPermissions(permissions: measurementUnitsAclBasePermissions());

        $content = $this->get(route('admin.measurement.families.edit', $family->id))
            ->assertOk()
            ->getContent();

        expect($content)->not->toContain(trans('measurement::app.measurement.unit.conversion_operation'))
            ->and($content)->not->toContain(trans('measurement::app.measurement.unit.create_unit'))
            ->and($content)->not->toContain(trans('measurement::app.measurement.edit.create_units'))
            ->and($content)->not->toContain("a.index === 'edit'")
            ->and($content)->not->toContain("a.index === 'delete'");
    });
});

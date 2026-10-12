<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\Core\Tree;
use Webkul\Measurement\Models\MeasurementFamily;

uses(ApiHelperTrait::class);

function measurementAclTree(): Tree
{
    $tree = Tree::create();

    foreach (config('api-acl') as $item) {
        $tree->add($item, 'acl');
    }

    return $tree;
}

function measurementRouteClaims(): array
{
    $claims = [];

    foreach (config('api-acl') as $item) {
        if (empty($item['route'])) {
            continue;
        }

        $claims[$item['route']][] = $item['key'];
    }

    return $claims;
}

function measurementUnitTestFamily(): MeasurementFamily
{
    return MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'units'         => [
            [
                'code'                  => 'meter',
                'labels'                => ['en_US' => 'Meter'],
                'symbol'                => 'm',
                'convert_from_standard' => [['operator' => 'mul', 'value' => '1']],
            ],
            [
                'code'                  => 'km',
                'labels'                => ['en_US' => 'Kilometer'],
                'symbol'                => 'km',
                'convert_from_standard' => [['operator' => 'mul', 'value' => '1000']],
            ],
        ],
    ]);
}

function measurementUnitRouteCalls(MeasurementFamily $family): array
{
    return [
        'index'  => ['getJson', route('admin.api.measurement-units.index', $family->code), null],
        'show'   => ['getJson', route('admin.api.measurement-units.show', [$family->code, 'km']), null],
        'store'  => ['postJson', route('admin.api.measurement-units.store', $family->code), []],
        'update' => ['putJson', route('admin.api.measurement-units.update', [$family->code, 'km']), []],
        'delete' => ['deleteJson', route('admin.api.measurement-units.delete', [$family->code, 'km']), null],
    ];
}

describe('measurement api acl tree', function () {
    it('maps each route to the expected permission key', function (string $route, string $key) {
        expect(measurementAclTree()->roles[$route] ?? null)->toBe($key);
        expect(app('api-acl')->roles[$route] ?? null)->toBe($key);
    })->with([
        ['admin.api.measurement-units.index', 'api.catalog.measurements.units'],
        ['admin.api.measurement-units.show', 'api.catalog.measurements.units'],
        ['admin.api.measurement-units.store', 'api.catalog.measurements.units.create'],
        ['admin.api.measurement-units.update', 'api.catalog.measurements.units.edit'],
        ['admin.api.measurement-units.delete', 'api.catalog.measurements.units.delete'],
        ['admin.api.measurement.index', 'api.catalog.measurements'],
        ['admin.api.measurement.show', 'api.catalog.measurements'],
        ['admin.api.measurement.store', 'api.catalog.measurements.create'],
        ['admin.api.measurement.update', 'api.catalog.measurements.edit'],
        ['admin.api.measurement.delete', 'api.catalog.measurements.delete'],
        ['admin.api.attribute-measurement.show', 'api.catalog.measurements'],
        ['admin.api.attribute-measurement.getUnitsByFamily', 'api.catalog.measurements'],
        ['admin.api.attribute-measurement.store', 'api.catalog.measurements.create'],
        ['admin.api.attribute-measurement.update', 'api.catalog.measurements.edit'],
    ]);

    it('never lets a unit route fall back to a family permission key', function () {
        $unitRoutes = [
            'admin.api.measurement-units.index',
            'admin.api.measurement-units.show',
            'admin.api.measurement-units.store',
            'admin.api.measurement-units.update',
            'admin.api.measurement-units.delete',
        ];

        $familyKeys = [
            'api.catalog.measurements',
            'api.catalog.measurements.create',
            'api.catalog.measurements.edit',
            'api.catalog.measurements.delete',
        ];

        foreach ($unitRoutes as $route) {
            expect(app('api-acl')->roles[$route] ?? null)->not->toBeIn($familyKeys);
        }
    });

    it('does not claim any measurement route under more than one key', function () {
        $conflicting = [];

        foreach (measurementRouteClaims() as $route => $keys) {
            if (! str_contains($route, 'measurement')) {
                continue;
            }

            if (count(array_unique($keys)) > 1) {
                $conflicting[$route] = array_values(array_unique($keys));
            }
        }

        expect($conflicting)->toBe([]);
    });

    it('does not let another package overwrite a measurement acl entry', function () {
        $packageConfig = require base_path('packages/Webkul/Measurement/src/Config/api-acl.php');

        $claims = measurementRouteClaims();

        $overwritten = [];

        foreach ($packageConfig as $item) {
            $keys = array_unique($claims[$item['route']] ?? []);

            if (count($keys) > 1) {
                $overwritten[$item['route']] = array_values($keys);
            }
        }

        expect($overwritten)->toBe([]);
    });

    it('registers the units node with its own create, edit and delete children', function () {
        $measurements = measurementAclTree()->items['api']['children']['catalog']['children']['measurements'] ?? null;

        expect($measurements)->not->toBeNull();

        $units = $measurements['children']['units'] ?? null;

        expect($units)->not->toBeNull()
            ->and($units['key'])->toBe('api.catalog.measurements.units')
            ->and(array_keys($units['children']))->toEqualCanonicalizing(['create', 'edit', 'delete'])
            ->and($units['children']['create']['key'])->toBe('api.catalog.measurements.units.create')
            ->and($units['children']['edit']['key'])->toBe('api.catalog.measurements.units.edit')
            ->and($units['children']['delete']['key'])->toBe('api.catalog.measurements.units.delete');
    });
});

describe('measurement unit api enforcement', function () {
    it('forbids every unit route for a key holding only the family permissions', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom', [
            'api.catalog.measurements',
            'api.catalog.measurements.create',
            'api.catalog.measurements.edit',
            'api.catalog.measurements.delete',
        ]);

        foreach (measurementUnitRouteCalls($family) as $name => [$method, $url, $payload]) {
            $response = $payload === null
                ? $this->withHeaders($headers)->{$method}($url)
                : $this->withHeaders($headers)->{$method}($url, $payload);

            expect($response->status())->toBe(403, $name.' should be forbidden');
        }
    });

    it('still allows the family read routes for a family view key', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements']);

        $this->withHeaders($headers)->getJson(route('admin.api.measurement.index'))->assertOk();
        $this->withHeaders($headers)->getJson(route('admin.api.measurement.show', $family->code))->assertOk();
        $this->withHeaders($headers)
            ->getJson(route('admin.api.attribute-measurement.getUnitsByFamily', $family->code))
            ->assertOk();
    });

    it('still allows the family store route for a family create key', function () {
        $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements.create']);

        expect(
            $this->withHeaders($headers)->postJson(route('admin.api.measurement.store'), [])->status()
        )->not->toBe(403);
    });

    it('still allows the family update route for a family edit key', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements.edit']);

        expect(
            $this->withHeaders($headers)->putJson(route('admin.api.measurement.update', $family->code), [])->status()
        )->not->toBe(403);
    });

    it('still allows the family delete route for a family delete key', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements.delete']);

        expect(
            $this->withHeaders($headers)->deleteJson(route('admin.api.measurement.delete', $family->code))->status()
        )->not->toBe(403);
    });

    it('allows the unit read routes for a key holding the units view permission', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements.units']);

        $this->withHeaders($headers)
            ->getJson(route('admin.api.measurement-units.index', $family->code))
            ->assertOk();

        $this->withHeaders($headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'km']))
            ->assertOk();
    });

    it('allows the unit store route for a units create key', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements.units.create']);

        expect(
            $this->withHeaders($headers)
                ->postJson(route('admin.api.measurement-units.store', $family->code), [])
                ->status()
        )->not->toBe(403);
    });

    it('allows the unit update route for a units edit key', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements.units.edit']);

        $this->withHeaders($headers)
            ->putJson(route('admin.api.measurement-units.update', [$family->code, 'km']), ['symbol' => 'KM'])
            ->assertOk();
    });

    it('allows the unit delete route for a units delete key', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements.units.delete']);

        $this->withHeaders($headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [$family->code, 'km']))
            ->assertOk();
    });

    it('does not imply sibling unit permissions from a single unit node', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements.units.create']);

        $this->withHeaders($headers)
            ->getJson(route('admin.api.measurement-units.index', $family->code))
            ->assertForbidden();

        $this->withHeaders($headers)
            ->putJson(route('admin.api.measurement-units.update', [$family->code, 'km']), [])
            ->assertForbidden();

        $this->withHeaders($headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [$family->code, 'km']))
            ->assertForbidden();
    });

    it('does not let the units view permission open the unit write routes', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements.units']);

        $this->withHeaders($headers)
            ->postJson(route('admin.api.measurement-units.store', $family->code), [])
            ->assertForbidden();

        $this->withHeaders($headers)
            ->putJson(route('admin.api.measurement-units.update', [$family->code, 'km']), [])
            ->assertForbidden();

        $this->withHeaders($headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [$family->code, 'km']))
            ->assertForbidden();
    });

    it('allows every measurement route for a full access key', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('all');

        foreach (measurementUnitRouteCalls($family) as $name => [$method, $url, $payload]) {
            $response = $payload === null
                ? $this->withHeaders($headers)->{$method}($url)
                : $this->withHeaders($headers)->{$method}($url, $payload);

            expect($response->status())->not->toBe(403, $name.' should not be forbidden');
        }

        $this->withHeaders($headers)->getJson(route('admin.api.measurement.index'))->assertOk();
    });

    it('forbids every unit route for a custom key with no permissions at all', function () {
        $family = measurementUnitTestFamily();

        $headers = $this->getAuthenticationHeaders('custom');

        foreach (measurementUnitRouteCalls($family) as $name => [$method, $url, $payload]) {
            $response = $payload === null
                ? $this->withHeaders($headers)->{$method}($url)
                : $this->withHeaders($headers)->{$method}($url, $payload);

            expect($response->status())->toBe(403, $name.' should be forbidden');
        }
    });
});

describe('measurement unit acl translations', function () {
    it('resolves every label used by the unit permission nodes', function (string $key) {
        $value = trans($key);

        expect($value)->toBeString()
            ->not->toBe($key)
            ->not->toBe('');
    })->with([
        'measurement::app.acl.units',
        'admin::app.acl.create',
        'admin::app.acl.edit',
        'admin::app.acl.delete',
    ]);

    it('defines the units acl label in every measurement locale', function () {
        $localeDirs = glob(base_path('packages/Webkul/Measurement/src/Resources/lang/*'), GLOB_ONLYDIR) ?: [];

        expect($localeDirs)->toHaveCount(33);

        $missing = [];

        foreach ($localeDirs as $dir) {
            $file = $dir.'/app.php';

            if (! is_file($file)) {
                $missing[] = basename($dir).': missing app.php';

                continue;
            }

            $lang = require $file;

            $value = data_get($lang, 'acl.units');

            if (! is_string($value) || trim($value) === '') {
                $missing[] = basename($dir).': acl.units';
            }
        }

        expect($missing)->toBe([]);
    });

    it('defines the shared create, edit and delete acl labels in every admin locale', function () {
        $localeDirs = glob(base_path('packages/Webkul/Admin/src/Resources/lang/*'), GLOB_ONLYDIR) ?: [];

        expect(count($localeDirs))->toBeGreaterThan(0);

        $missing = [];

        foreach ($localeDirs as $dir) {
            $file = $dir.'/app.php';

            if (! is_file($file)) {
                $missing[] = basename($dir).': missing app.php';

                continue;
            }

            $lang = require $file;

            foreach (['create', 'edit', 'delete'] as $child) {
                $value = data_get($lang, 'acl.'.$child);

                if (! is_string($value) || trim($value) === '') {
                    $missing[] = basename($dir).': acl.'.$child;
                }
            }
        }

        expect($missing)->toBe([]);
    });
});

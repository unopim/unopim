<?php

use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;
use Webkul\DataTransfer\Services\JobTypeGate;

uses(InteractsWithPlatformModules::class);

beforeEach(function (): void {
    config(['platform_modules.records' => ['name' => 'records', 'requires' => []]]);
});

$shared = ['users', 'roles', 'channels', 'locales', 'currencies', 'attributes', 'attribute-groups', 'attribute-options'];
$pimOnly = ['products', 'categories', 'category-fields', 'attribute-families', 'product-associations'];

it('keeps shared import/export types available when PIM is off', function () use ($shared): void {
    $this->setPlatformModules('records');

    $gate = app(JobTypeGate::class);

    foreach (['importers', 'exporters'] as $registry) {
        $visible = array_keys($gate->visible($registry));

        foreach ($shared as $type) {
            expect(in_array($type, $visible, true))->toBeTrue("$type should stay in $registry with PIM off");
            expect($gate->hides($type))->toBeFalse("$type must not 404 with PIM off");
        }
    }
});

it('removes PIM-data import/export types when PIM is off', function () use ($pimOnly): void {
    $this->setPlatformModules('records');

    $gate = app(JobTypeGate::class);

    foreach (['importers', 'exporters'] as $registry) {
        $visible = array_keys($gate->visible($registry));

        foreach ($pimOnly as $type) {
            expect(in_array($type, $visible, true))->toBeFalse("$type should be removed from $registry with PIM off");
            expect($gate->hides($type))->toBeTrue("$type should 404 with PIM off");
        }
    }
});

it('keeps every type available when PIM is on', function () use ($shared, $pimOnly): void {
    $this->setPlatformModules('pim');

    $gate = app(JobTypeGate::class);

    foreach (['importers', 'exporters'] as $registry) {
        $visible = array_keys($gate->visible($registry));

        foreach ([...$shared, ...$pimOnly] as $type) {
            expect(in_array($type, $visible, true))->toBeTrue("$type should be present in $registry with PIM on");
        }
    }
});

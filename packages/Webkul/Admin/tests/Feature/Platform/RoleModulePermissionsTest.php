<?php

use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;
use Webkul\User\Models\Role;

uses(InteractsWithPlatformModules::class);

beforeEach(function () {
    config(['platform_modules.records' => ['name' => 'records', 'requires' => []]]);
});

it('drops a crafted permission of a switched-off module when a role is created', function () {
    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $name = 'gated-role-'.uniqid();

    $this->postJson(route('admin.settings.roles.store'), [
        'name'            => $name,
        'permission_type' => 'custom',
        'permissions'     => ['settings.locales', 'catalog.products'],
    ])->assertSuccessful();

    expect(Role::query()->where('name', $name)->firstOrFail()->permissions)->toBe(['settings.locales']);
});

it('stores every submitted permission when the role is created with pim on', function () {
    $this->setPlatformModules('pim');

    $this->loginAsAdmin();

    $name = 'gated-role-'.uniqid();

    $this->postJson(route('admin.settings.roles.store'), [
        'name'            => $name,
        'permission_type' => 'custom',
        'permissions'     => ['settings.locales', 'catalog.products'],
    ])->assertSuccessful();

    expect(Role::query()->where('name', $name)->firstOrFail()->permissions)->toBe(['settings.locales', 'catalog.products']);
});

it('keeps stored permissions of a switched-off module and drops crafted ones on role update', function () {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['settings.locales', 'catalog.products'],
    ]);

    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $this->putJson(route('admin.settings.roles.update', $role->id), [
        'name'            => $role->name,
        'permission_type' => 'custom',
        'permissions'     => ['settings.channels', 'catalog.categories'],
    ])->assertSuccessful();

    expect($role->refresh()->permissions)->toEqualCanonicalizing(['settings.channels', 'catalog.products']);
});

it('keeps stored permissions of a switched-off module when a role update submits none', function () {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['settings.locales', 'catalog.products'],
    ]);

    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $this->putJson(route('admin.settings.roles.update', $role->id), [
        'name'            => $role->name,
        'permission_type' => 'custom',
    ])->assertSuccessful();

    expect($role->refresh()->permissions)->toBe(['catalog.products']);
});

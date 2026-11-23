<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Platform\ModuleGate;
use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;

uses(InteractsWithPlatformModules::class);

beforeEach(function () {
    config(['platform_modules.records' => ['name' => 'records', 'requires' => []]]);

    CoreConfig::query()->where('code', ModuleGate::HIDDEN_SECTIONS_KEY)->delete();
});

function storeGateHiddenSections(string $value): void
{
    CoreConfig::query()->updateOrCreate(
        ['code' => ModuleGate::HIDDEN_SECTIONS_KEY, 'channel_code' => null, 'locale_code' => null],
        ['value' => $value],
    );

    app()->forgetScopedInstances();
}

function gatedAdminMenuItems(): array
{
    app()->forgetScopedInstances();

    return app('unopim.admin.menu')['tree']->items;
}

it('responds 404 for an admin route of a switched-off module', function () {
    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $this->get(route('admin.catalog.products.index'))->assertNotFound();
});

it('serves the same admin route while its module is on', function () {
    $this->setPlatformModules('pim');

    $this->loginAsAdmin();

    $this->get(route('admin.catalog.products.index'))->assertOk();
});

it('responds 404 for a section hidden by default while pim is off', function () {
    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $this->get(route('admin.dashboard.index'))->assertNotFound();
});

it('serves a hidden-by-default section once the admin unhides it', function () {
    $this->setPlatformModules('records');

    storeGateHiddenSections('');

    $this->loginAsAdmin();

    $this->get(route('admin.catalog.attributes.index'))->assertOk();
});

it('lands a logged-in admin on a reachable page when pim is off', function () {
    $this->setPlatformModules('records');

    $this->loginAsAdmin();

    $location = $this->get(route('admin.session.create'))->assertRedirect()->headers->get('Location');

    expect($location)->not->toBe(route('admin.catalog.products.index'))
        ->and($location)->not->toBe(route('admin.dashboard.index'))
        ->and($location)->not->toBe(route('admin.session.create'));

    $this->get($location)->assertOk();
});

it('lands a logged-in admin on the dashboard when pim is on', function () {
    $this->setPlatformModules('pim');

    $this->loginAsAdmin();

    $this->get(route('admin.session.create'))->assertRedirect(route('admin.dashboard.index'));
});

it('drops the products menu and the catalog parent left without children when pim is off', function () {
    $this->setPlatformModules('records');

    $this->loginWithPermissions('all');

    $items = gatedAdminMenuItems();

    expect($items)->not->toHaveKey('catalog')
        ->and($items)->not->toHaveKey('dashboard')
        ->and($items)->toHaveKey('settings')
        ->and($items)->toHaveKey('data_transfer')
        ->and($items['settings']['children'])->toHaveKey('locales');
});

it('keeps the catalog parent with its untagged children when pim is off and nothing is hidden', function () {
    $this->setPlatformModules('records');

    storeGateHiddenSections('');

    $this->loginWithPermissions('all');

    $children = gatedAdminMenuItems()['catalog']['children'] ?? [];

    expect($children)->toHaveKey('attributes')
        ->and($children)->not->toHaveKey('products')
        ->and($children)->not->toHaveKey('categories')
        ->and($children)->not->toHaveKey('families');
});

it('shows the products menu while pim is on', function () {
    $this->setPlatformModules('pim');

    $this->loginWithPermissions('all');

    expect(gatedAdminMenuItems()['catalog']['children'] ?? [])->toHaveKey('products');
});

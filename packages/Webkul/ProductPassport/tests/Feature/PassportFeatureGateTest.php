<?php

use Webkul\Core\Models\Channel;
use Webkul\Core\Models\CoreConfig;
use Webkul\ProductPassport\Services\PassportFeature;

function setPassportEnabled(?string $channelCode, string $value): void
{
    CoreConfig::query()->updateOrCreate(
        [
            'code'         => 'catalog.product_passport.settings.enabled',
            'channel_code' => $channelCode,
            'locale_code'  => null,
        ],
        ['value' => $value],
    );
}

function passportMenuItems(): array
{
    app()->forgetScopedInstances();

    return app('unopim.admin.menu')['tree']->items['catalog']['children'] ?? [];
}

it('reports the feature off while no scope enables it', function (): void {
    CoreConfig::query()->where('code', 'catalog.product_passport.settings.enabled')->delete();

    expect(resolve(PassportFeature::class)->enabledAnywhere())->toBeFalse();
});

it('reports the feature on from the global scope', function (): void {
    setPassportEnabled(null, '1');

    expect(resolve(PassportFeature::class)->enabledAnywhere())->toBeTrue();
});

it('keeps the feature on for a channel that opted in while the global scope is off', function (): void {
    $channel = Channel::query()->firstOrFail();

    setPassportEnabled(null, '0');
    setPassportEnabled($channel->code, '1');

    expect(resolve(PassportFeature::class)->enabledAnywhere())->toBeTrue()
        ->and(resolve(PassportFeature::class)->enabledFor($channel))->toBeTrue();
});

it('ignores an enabled row left behind by a channel that no longer exists', function (): void {
    CoreConfig::query()->where('code', 'catalog.product_passport.settings.enabled')->delete();

    setPassportEnabled('deleted_channel', '1');

    expect(resolve(PassportFeature::class)->enabledAnywhere())->toBeFalse();
});

it('drops the passports menu item once the global switch is turned off', function (): void {
    CoreConfig::query()->where('code', 'catalog.product_passport.settings.enabled')->delete();

    $this->loginWithPermissions('all');

    setPassportEnabled(null, '1');

    expect(passportMenuItems())->toHaveKey('passport');

    setPassportEnabled(null, '0');

    expect(passportMenuItems())->not->toHaveKey('passport');
});

it('404s the passports grid once the global switch is turned off', function (): void {
    CoreConfig::query()->where('code', 'catalog.product_passport.settings.enabled')->delete();

    $this->loginWithPermissions('all');

    setPassportEnabled(null, '0');

    $this->get(route('admin.catalog.passports.index'))->assertNotFound();
});

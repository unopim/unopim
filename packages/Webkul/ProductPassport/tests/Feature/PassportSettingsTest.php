<?php

use Illuminate\Support\Facades\Artisan;

it('registers all three levels of the passport settings tree and defaults auto_publish off', function (): void {
    $keys = collect(config('core'))->pluck('key');

    expect($keys)->toContain('catalog')
        ->and($keys)->toContain('catalog.product_passport')
        ->and($keys)->toContain('catalog.product_passport.settings')
        ->and((bool) (core()->getConfigData('catalog.product_passport.settings.auto_publish') ?? false))->toBeFalse();
});

it('exposes the section through the system settings hub', function (): void {
    expect(collect(config('system_settings'))->pluck('key'))->toContain('digital_product_passport.product_passport');
});

it('passes the translations checker for the product passport package', function (): void {
    Artisan::call('unopim:translations:check', ['--package' => 'ProductPassport']);

    expect(Artisan::output())->not->toContain('No translation files discovered');
});

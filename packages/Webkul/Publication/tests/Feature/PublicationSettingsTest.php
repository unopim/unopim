<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Webkul\Publication\Services\Publisher;
use Webkul\Publication\Tests\Support\StubPayloadBuilder;

it('registers all three levels of the publication settings tree', function (): void {
    $keys = collect(config('core'))->pluck('key');

    expect($keys)->toContain('general')
        ->and($keys)->toContain('general.publication')
        ->and($keys)->toContain('general.publication.settings');
});

it('exposes the section through the system settings hub', function (): void {
    expect(collect(config('system_settings'))->pluck('key'))->toContain('digital_product_passport.publication');
});

it('defaults every field before any core_config row exists', function (): void {
    expect(core()->getConfigData('general.publication.settings.rate_limit') ?? '60')->toBe('60')
        ->and(core()->getConfigData('general.publication.settings.indexable') ?? '0')->toBe('0');
});

it('prefers the configured base_url over app.url when building the canonical url', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    config()->set('publication.types.dpp', [
        'label'           => 'publication::app.publications.status.draft',
        'payload_builder' => StubPayloadBuilder::class,
        'template'        => 'publication::dpp.show',
        'required_group'  => 'dpp_group',
        'route_prefix'    => 'dpp',
    ]);

    DB::table('core_config')->updateOrInsert(
        ['code' => 'general.publication.settings.base_url', 'channel_code' => $channel->code],
        ['value' => 'https://passports.example.com']
    );

    app('config')->set('core_config', null);

    $version = resolve(Publisher::class)->publish($product, $channel, $complete, 'dpp');

    expect($version->payload['meta']['url'])
        ->toStartWith('https://passports.example.com/dpp/')
        ->and($version->payload['meta']['url'])->not->toContain(config('app.url'));
});

it('falls back to app.url when no base_url is configured', function (): void {
    [$product, $channel, , $complete] = $this->seedPassportFixture();

    config()->set('publication.types.dpp', [
        'label'           => 'publication::app.publications.status.draft',
        'payload_builder' => StubPayloadBuilder::class,
        'template'        => 'publication::dpp.show',
        'required_group'  => 'dpp_group',
        'route_prefix'    => 'dpp',
    ]);

    $version = resolve(Publisher::class)->publish($product, $channel, $complete, 'dpp');

    expect($version->payload['meta']['url'])->toStartWith(rtrim((string) config('app.url'), '/').'/dpp/');
});

it('passes the translations checker for the publication package', function (): void {
    Artisan::call('unopim:translations:check', ['--package' => 'Publication']);

    expect(Artisan::output())->not->toContain('No translation files discovered');
});

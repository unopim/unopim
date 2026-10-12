<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Publication\Services\PublicAccessGate;

function publicTierRow(?string $channelCode, string $value): void
{
    CoreConfig::query()->create([
        'code'         => PublicAccessGate::ENABLED,
        'channel_code' => $channelCode,
        'locale_code'  => null,
        'value'        => $value,
    ]);
}

it('serves the passport page with a revalidated cache header rather than a blind max-age', function (): void {
    $version = $this->publishedPassportFixture();

    $response = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    $response->assertOk();

    expect($response->headers->get('Cache-Control'))
        ->toContain('max-age=0')
        ->toContain('must-revalidate')
        ->toContain('s-maxage=');
});

it('never lets a 404 from the kill switch be cached', function (): void {
    $version = $this->publishedPassportFixture();

    CoreConfig::query()->where('code', PublicAccessGate::ENABLED)->delete();

    publicTierRow($version->publication->channel->code, '0');

    $response = $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code);

    $response->assertNotFound();

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('honours the newest row when the same scope was written more than once', function (): void {
    $version = $this->publishedPassportFixture();
    $channelCode = $version->publication->channel->code;

    CoreConfig::query()->where('code', PublicAccessGate::ENABLED)->delete();

    publicTierRow($channelCode, '1');
    publicTierRow($channelCode, '0');

    expect(resolve(PublicAccessGate::class)->enabledForChannel($channelCode))->toBeFalse();

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)->assertNotFound();
});

it('reads the global row only when the channel has none, ignoring a stale row of another channel', function (): void {
    $version = $this->publishedPassportFixture();
    $channelCode = $version->publication->channel->code;

    CoreConfig::query()->where('code', PublicAccessGate::ENABLED)->delete();

    publicTierRow('some_other_channel', '1');
    publicTierRow(null, '0');

    expect(resolve(PublicAccessGate::class)->enabledForChannel($channelCode))->toBeFalse();

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)->assertNotFound();
});

it('lets a channel row override the global one', function (): void {
    $version = $this->publishedPassportFixture();
    $channelCode = $version->publication->channel->code;

    CoreConfig::query()->where('code', PublicAccessGate::ENABLED)->delete();

    publicTierRow(null, '0');
    publicTierRow($channelCode, '1');

    expect(resolve(PublicAccessGate::class)->enabledForChannel($channelCode))->toBeTrue();

    $this->get('/p/'.$version->publication->uuid.'/'.$version->locale->code)->assertOk();
});

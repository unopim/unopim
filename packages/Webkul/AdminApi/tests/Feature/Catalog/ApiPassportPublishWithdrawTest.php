<?php

use Illuminate\Support\Facades\Queue;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\Publication;

function enablePassportFeatureGlobally(): void
{
    CoreConfig::query()->updateOrCreate(
        ['code' => 'catalog.product_passport.settings.enabled', 'channel_code' => null, 'locale_code' => null],
        ['value' => '1'],
    );
}

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('queues a passport publish job', function () {
    enablePassportFeatureGlobally();
    Queue::fake();

    $product = Product::factory()->create();
    $channel = Channel::first();
    $locale = Locale::first();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.publish', $product->sku), [
            'channel_id' => $channel->id,
            'locale_ids' => [$locale->id],
        ])
        ->assertStatus(202)
        ->assertJson(['success' => true]);

    Queue::assertPushed(PublishPassportForProductChannelJob::class);
});

it('validates the publish payload', function () {
    enablePassportFeatureGlobally();
    $product = Product::factory()->create();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.publish', $product->sku), [
            'channel_id' => 999999,
            'locale_ids' => [],
        ])
        ->assertStatus(422);
});

it('returns 404 publishing an unknown sku', function () {
    enablePassportFeatureGlobally();
    $channel = Channel::first();
    $locale = Locale::first();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.publish', 'no-such-sku'), [
            'channel_id' => $channel->id,
            'locale_ids' => [$locale->id],
        ])
        ->assertNotFound();
});

it('withdraws a publication', function () {
    enablePassportFeatureGlobally();
    $publication = Publication::factory()->create(['status' => PublicationStatus::Published]);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.withdraw', $publication->id))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($publication->fresh()->status)->toBe(PublicationStatus::Withdrawn);
});

it('returns 404 withdrawing an unknown publication', function () {
    enablePassportFeatureGlobally();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.withdraw', 999999))
        ->assertNotFound();
});

it('forbids publish without the publish permission', function () {
    enablePassportFeatureGlobally();
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.passports']);
    $product = Product::factory()->create();
    $channel = Channel::first();
    $locale = Locale::first();

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.passports.publish', $product->sku), [
            'channel_id' => $channel->id,
            'locale_ids' => [$locale->id],
        ])
        ->assertForbidden();
});

it('rejects unauthenticated withdraw', function () {
    enablePassportFeatureGlobally();
    $publication = Publication::factory()->create();

    $this->json('POST', route('admin.api.passports.withdraw', $publication->id), [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});

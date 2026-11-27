<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;
use Webkul\ProductPassport\Models\PassportTemplate;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\Publication;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function enablePassportFeatureGlobally(): void
{
    CoreConfig::query()->updateOrCreate(
        ['code' => 'catalog.product_passport.settings.enabled', 'channel_code' => null, 'locale_code' => null],
        ['value' => '1'],
    );
}

/** Turns the public tier on or off for one channel. */
function setApiPassportPublicTier(string $channelCode, bool $enabled): void
{
    CoreConfig::query()->updateOrCreate(
        ['code' => 'general.publication.settings.enabled', 'channel_code' => $channelCode, 'locale_code' => null],
        ['value' => $enabled ? '1' : '0'],
    );
}

/**
 * A channel with one locale, the passport feature on and the public tier on.
 *
 * @return array{0: Channel, 1: Locale}
 */
function apiPassportChannel(): array
{
    enablePassportFeatureGlobally();

    $channel = ChannelProxy::factory()->create();
    $locale = $channel->locales()->first()
        ?: tap(Locale::factory()->create(), fn (Locale $createdLocale): mixed => $channel->locales()->attach($createdLocale));

    setApiPassportPublicTier($channel->code, true);

    return [$channel, $locale];
}

/** A product whose family has a passport template with one required field, filled for every locale or only for the given one. */
function apiPassportProduct(bool $withTemplate = true, bool $filled = true, ?string $filledLocale = null): Product
{
    $material = AttributeProxy::factory()->create([
        'code'             => 'src_'.Str::lower(Str::random(10)),
        'type'             => 'text',
        'value_per_locale' => $filledLocale !== null,
    ]);

    $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes($material)->create();

    if ($withTemplate) {
        $template = PassportTemplate::create([
            'code'       => 'tpl_'.Str::random(8),
            'is_enabled' => true,
            'en_US'      => ['name' => 'API passport template'],
        ]);

        $template->families()->attach($family->id);

        $section = $template->sections()->create(['code' => 'passport', 'position' => 0, 'en_US' => ['name' => 'Passport']]);

        $template->fields()->create([
            'code'                         => 'dpp_material_composition',
            'passport_template_section_id' => $section->id,
            'source_type'                  => 'attribute',
            'attribute_id'                 => $material->id,
            'tier'                         => 'consumer',
            'is_required'                  => true,
            'position'                     => 0,
            'en_US'                        => ['label' => 'Material Composition'],
        ]);
    }

    $value = [$material->code => 'Recycled cotton, 80%'];

    $values = match (true) {
        ! $filled              => [],
        $filledLocale === null => ['common' => $value],
        default                => ['locale_specific' => [$filledLocale => $value]],
    };

    return ProductProxy::factory()->create(['attribute_family_id' => $family->id, 'values' => $values]);
}

/**
 * Posts an API publish request for the product.
 *
 * @param  array<int, int>  $localeIds
 */
function postApiPassportPublish(mixed $test, Product $product, int $channelId, array $localeIds): TestResponse
{
    return $test->withHeaders($test->headers)
        ->json('POST', route('admin.api.passports.publish', $product->sku), [
            'channel_id' => $channelId,
            'locale_ids' => $localeIds,
        ]);
}

beforeEach(function (): void {
    $this->headers = $this->getAuthenticationHeaders();
});

it('queues a passport publish job', function (): void {
    [$channel, $locale] = apiPassportChannel();
    Queue::fake();

    postApiPassportPublish($this, apiPassportProduct(), $channel->id, [$locale->id])
        ->assertStatus(202)
        ->assertJson(['success' => true]);

    Queue::assertPushed(PublishPassportForProductChannelJob::class);
});

it('refuses a locale that is not assigned to the channel', function (): void {
    [$channel] = apiPassportChannel();
    $otherLocale = Locale::factory()->create();
    Queue::fake();

    postApiPassportPublish($this, apiPassportProduct(), $channel->id, [$otherLocale->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('locale_ids.0');

    Queue::assertNotPushed(PublishPassportForProductChannelJob::class);
});

it('refuses a repeated locale', function (): void {
    [$channel, $locale] = apiPassportChannel();
    Queue::fake();

    postApiPassportPublish($this, apiPassportProduct(), $channel->id, [$locale->id, $locale->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('locale_ids.0');

    Queue::assertNotPushed(PublishPassportForProductChannelJob::class);
});

it('refuses to publish while the channel public tier is off', function (): void {
    [$channel, $locale] = apiPassportChannel();
    setApiPassportPublicTier($channel->code, false);
    Queue::fake();

    postApiPassportPublish($this, apiPassportProduct(), $channel->id, [$locale->id])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', trans('passport::app.publications.public-access-disabled'));

    Queue::assertNotPushed(PublishPassportForProductChannelJob::class);
});

it('reports the locales a product cannot be published in yet', function (bool $withTemplate, bool $filled, string $status): void {
    [$channel, $locale] = apiPassportChannel();
    Queue::fake();

    postApiPassportPublish($this, apiPassportProduct($withTemplate, $filled), $channel->id, [$locale->id])
        ->assertUnprocessable()
        ->assertJsonPath('message', trans('passport::app.catalog.products.edit.passport.publish-blocked'))
        ->assertJsonPath('blocked_locales.0.locale_code', $locale->code)
        ->assertJsonPath('blocked_locales.0.status', $status);

    Queue::assertNotPushed(PublishPassportForProductChannelJob::class);
})->with([
    'no passport template'    => [false, true, 'missing_template'],
    'required field is empty' => [true, false, 'missing_fields'],
]);

it('refuses the whole publish and lists only the locales that are not ready', function (): void {
    [$channel, $readyLocale] = apiPassportChannel();
    $emptyLocale = Locale::factory()->create();
    $channel->locales()->attach($emptyLocale);
    Queue::fake();

    postApiPassportPublish($this, apiPassportProduct(filledLocale: $readyLocale->code), $channel->id, [$readyLocale->id, $emptyLocale->id])
        ->assertUnprocessable()
        ->assertJsonCount(1, 'blocked_locales')
        ->assertJsonPath('blocked_locales.0.locale_code', $emptyLocale->code)
        ->assertJsonPath('blocked_locales.0.status', 'missing_fields');

    Queue::assertNotPushed(PublishPassportForProductChannelJob::class);
});

it('refuses to publish a withdrawn or redacted passport', function (PublicationStatus $status): void {
    [$channel, $locale] = apiPassportChannel();
    $product = apiPassportProduct();
    Publication::factory()->create(['product_id' => $product->id, 'channel_id' => $channel->id, 'type' => 'dpp', 'status' => $status]);
    Queue::fake();

    postApiPassportPublish($this, $product, $channel->id, [$locale->id])
        ->assertUnprocessable()
        ->assertJsonPath('message', trans('passport::app.publications.publish-withdrawn'));

    Queue::assertNotPushed(PublishPassportForProductChannelJob::class);
})->with([
    'withdrawn' => [PublicationStatus::Withdrawn],
    'redacted'  => [PublicationStatus::Redacted],
]);

it('refuses with the same body as the admin publish', function (string $case): void {
    [$channel, $locale] = apiPassportChannel();
    $product = apiPassportProduct(filled: $case !== 'blocked locale');

    match ($case) {
        'public tier off' => setApiPassportPublicTier($channel->code, false),
        'withdrawn'       => Publication::factory()->create(['product_id' => $product->id, 'channel_id' => $channel->id, 'type' => 'dpp', 'status' => PublicationStatus::Withdrawn]),
        default           => null,
    };

    Queue::fake();

    $admin = Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]);

    $adminBody = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.passports.publish', $product->id), ['channel_id' => $channel->id, 'locale_ids' => [$locale->id]])
        ->assertUnprocessable()
        ->json();

    $apiBody = postApiPassportPublish($this, $product, $channel->id, [$locale->id])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->json();

    unset($apiBody['success']);

    expect($apiBody)->toBe($adminBody);

    Queue::assertNotPushed(PublishPassportForProductChannelJob::class);
})->with(['public tier off', 'blocked locale', 'withdrawn']);

it('validates the publish payload', function (): void {
    enablePassportFeatureGlobally();
    $product = Product::factory()->create();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.publish', $product->sku), [
            'channel_id' => 999999,
            'locale_ids' => [],
        ])
        ->assertStatus(422);
});

it('returns 404 publishing an unknown sku', function (): void {
    [$channel, $locale] = apiPassportChannel();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.publish', 'no-such-sku'), [
            'channel_id' => $channel->id,
            'locale_ids' => [$locale->id],
        ])
        ->assertNotFound();
});

it('withdraws a publication', function (): void {
    enablePassportFeatureGlobally();
    $publication = Publication::factory()->create(['status' => PublicationStatus::Published]);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.withdraw', $publication->id))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($publication->fresh()->status)->toBe(PublicationStatus::Withdrawn);
});

it('returns 404 withdrawing an unknown publication', function (): void {
    enablePassportFeatureGlobally();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.withdraw', 999999))
        ->assertNotFound();
});

it('forbids publish without the publish permission', function (): void {
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

it('rejects unauthenticated withdraw', function (): void {
    enablePassportFeatureGlobally();
    $publication = Publication::factory()->create();

    $this->json('POST', route('admin.api.passports.withdraw', $publication->id), [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});

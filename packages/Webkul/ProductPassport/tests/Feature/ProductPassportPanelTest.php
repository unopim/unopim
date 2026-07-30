<?php

use Illuminate\Support\Facades\Queue;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\ProductProxy;
use Webkul\ProductPassport\Services\PassportFeature;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Services\Publisher;

it('renders the panel into the product edit page for an authorised admin', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => $context->channel->code, 'locale' => $context->locale->code]))
        ->assertOk()
        ->assertSee('id="passport-panel"', false);
});

it('renders the panel as a drawer card with locales and history tabs', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => $context->channel->code, 'locale' => $context->locale->code]))
        ->assertOk()
        ->assertSee('v-product-section-drawer', false)
        ->assertSee('class="passport-locales-table overflow-x-auto"', false)
        ->assertSee('value="passport-locales"', false)
        ->assertSee('value="passport-history"', false);
});

it('summarises how many locales are published on the drawer card', function (): void {
    [$product, $channel, , $complete] = $this->productWithTwoDppLocales();

    $this->enablePassportPublishing($channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => $channel->code, 'locale' => $complete->code]))
        ->assertOk()
        ->assertSee(trans('passport::app.catalog.products.edit.passport.published-summary', [
            'published' => 0,
            'total'     => $channel->locales()->count(),
        ]));
});

it('does not render the panel for an admin without view permission', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('custom', ['catalog.products', 'catalog.products.edit']);

    $this->get(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => $context->channel->code, 'locale' => $context->locale->code]))
        ->assertOk()
        ->assertDontSee('id="passport-panel"', false);
});

it('shows per-locale passport status with missing field counts', function (): void {
    [$product, $channel, $incomplete, $complete] = $this->productWithTwoDppLocales();

    $this->enablePassportPublishing($channel->code);

    $this->loginWithPermissions('all');

    $this->getJson(route('admin.catalog.products.passport.show', ['product' => $product, 'channel' => $channel->code]))
        ->assertOk()
        ->assertJsonFragment([
            'locale_code'   => $complete->code,
            'ready'         => true,
            'missing_count' => 0,
        ])
        ->assertJsonFragment([
            'locale_code'   => $incomplete->code,
            'ready'         => false,
            'missing_count' => 1,
        ]);
});

it('refuses to publish a locale that fails the completeness gate', function (): void {
    [$product, $channel, $incomplete] = $this->productWithTwoDppLocales();

    $this->enablePassportPublishing($channel->code);

    $this->loginWithPermissions('all');

    Queue::fake();

    $this->postJson(route('admin.catalog.passports.publish', $product), [
        'channel_id' => $channel->id,
        'locale_ids' => [$incomplete->id],
    ])
        ->assertUnprocessable()
        ->assertJsonPath('blocked_locales.0.locale_code', $incomplete->code)
        ->assertJsonPath('blocked_locales.0.status', 'missing_fields')
        ->assertJsonPath('blocked_locales.0.missing_fields.0.label', 'Material Composition');

    Queue::assertNotPushed(PublishPassportForProductChannelJob::class);
    $this->assertDatabaseMissing('publications', ['product_id' => $product->id]);
});

it('publishes a ready locale after preflight passes', function (): void {
    [$product, $channel, , $complete] = $this->productWithTwoDppLocales();

    $this->enablePassportPublishing($channel->code);

    $this->loginWithPermissions('all');

    $this->postJson(route('admin.catalog.passports.publish', $product), [
        'channel_id' => $channel->id,
        'locale_ids' => [$complete->id],
    ])->assertOk();

    $this->assertDatabaseHas('publications', [
        'product_id' => $product->id,
        'channel_id' => $channel->id,
        'type'       => 'dpp',
    ]);
});

it('shows actionable DPP requirements and disables blocked publish actions', function (): void {
    [$product, $channel, $incomplete] = $this->productWithTwoDppLocales();

    $this->enablePassportPublishing($channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.products.edit', [
        'id'      => $product->id,
        'channel' => $channel->code,
        'locale'  => $incomplete->code,
    ]))
        ->assertOk()
        ->assertSee('data-requirement="dpp"', false)
        ->assertSee(trans('passport::app.catalog.products.edit.passport.required-badge'))
        ->assertSee('Material Composition')
        ->assertSee('passport-publish-all-btn primary-button shrink-0', false)
        ->assertSee('disabled', false);
});

it('hides DPP guidance and rejects publishing when the channel feature is disabled', function (): void {
    [$product, $channel, $incomplete, $complete] = $this->productWithTwoDppLocales();

    CoreConfig::query()
        ->where('code', 'catalog.product_passport.settings.enabled')
        ->where('channel_code', $channel->code)
        ->whereNull('locale_code')
        ->delete();

    CoreConfig::query()->create([
        'code'         => 'catalog.product_passport.settings.enabled',
        'channel_code' => $channel->code,
        'locale_code'  => null,
        'value'        => '0',
    ]);

    expect(resolve(PassportFeature::class)->enabledFor($channel))->toBeFalse();

    $this->loginWithPermissions('all');

    $response = $this->get(route('admin.catalog.products.edit', [
        'id'      => $product->id,
        'channel' => $channel->code,
        'locale'  => $incomplete->code,
    ]));

    $response->assertOk();

    $publishResponse = $this->postJson(route('admin.catalog.passports.publish', $product), [
        'channel_id' => $channel->id,
        'locale_ids' => [$incomplete->id],
    ]);

    expect($response->getContent())
        ->not->toContain('id="passport-panel"')
        ->not->toContain('data-requirement="dpp"')
        ->and($publishResponse->getStatusCode())->toBe(403)
        ->and(resolve(Publisher::class)->publish($product, $channel, $complete, 'dpp'))->toBeNull();

    $this->assertDatabaseMissing('publications', ['product_id' => $product->id]);
});

it('does not render publish controls for an admin with view-only passport access', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('custom', [
        'catalog.products',
        'catalog.products.edit',
        'catalog.passport',
        'catalog.passport.view',
    ]);

    $response = $this->get(route('admin.catalog.products.edit', [
        'id'      => $product->id,
        'channel' => $context->channel->code,
        'locale'  => $context->locale->code,
    ]));

    $response->assertOk();

    expect(bouncer()->hasPermission('catalog.passport.publish'))->toBeFalse()
        ->and($response->getContent())->toContain('id="passport-panel"')
        ->not->toContain('class="passport-publish-all-btn primary-button shrink-0"')
        ->not->toContain('class="passport-publish-btn primary-button"');
});

it('explains that an enabled template is required before publishing', function (): void {
    $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();
    $product = ProductProxy::factory()->create(['attribute_family_id' => $family->id]);
    $channel = ChannelProxy::factory()->create();
    $locale = $channel->locales()->first()
        ?: tap(Locale::factory()->create(), fn ($createdLocale) => $channel->locales()->attach($createdLocale));

    $this->enablePassportPublishing($channel->code);

    $this->loginWithPermissions('all');

    $response = $this->get(route('admin.catalog.products.edit', [
        'id'      => $product->id,
        'channel' => $channel->code,
        'locale'  => $locale->code,
    ]));

    $response->assertOk();

    expect($response->getContent())
        ->toContain(trans('passport::app.catalog.products.edit.passport.missing-template'))
        ->toContain('disabled');

    $publishResponse = $this->postJson(route('admin.catalog.passports.publish', $product), [
        'channel_id' => $channel->id,
        'locale_ids' => [$locale->id],
    ]);

    expect($publishResponse->getStatusCode())->toBe(422)
        ->and($publishResponse->json('blocked_locales.0.status'))->toBe('missing_template');

    $this->assertDatabaseMissing('publications', ['product_id' => $product->id]);
});

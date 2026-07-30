<?php

use Webkul\Publication\Models\Publication;

it('renders the panel into the product edit page for an authorised admin', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => $context->channel->code, 'locale' => $context->locale->code]))
        ->assertOk()
        // The panel id, not its heading: the fixture's attribute group carries the same label.
        ->assertSee('id="passport-panel"', false);
});

it('renders the panel as a drawer card with locales and history tabs', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => $context->channel->code, 'locale' => $context->locale->code]))
        ->assertOk()
        ->assertSee('v-product-section-drawer', false)
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

    $this->loginWithPermissions('all');

    $this->getJson(route('admin.catalog.products.passport.show', ['product' => $product, 'channel' => $channel->code]))
        ->assertOk()
        ->assertJsonFragment(['locale_code' => $complete->code])
        ->assertJsonFragment(['locale_code' => $incomplete->code]);
});

it('refuses to publish a locale that fails the completeness gate', function (): void {
    [$product, $channel, $incomplete] = $this->productWithTwoDppLocales();

    $this->enablePassportPublishing($channel->code);

    $this->loginWithPermissions('all');

    $this->postJson(route('admin.catalog.passports.publish', $product), [
        'channel_id' => $channel->id,
        'locale_ids' => [$incomplete->id],
    ])->assertOk();

    // sync queue in tests: the dispatched job already ran inline.
    expect(Publication::where('product_id', $product->id)->exists())->toBeFalse();
});

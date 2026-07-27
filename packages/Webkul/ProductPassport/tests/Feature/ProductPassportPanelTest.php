<?php

use Webkul\Publication\Models\Publication;

it('renders the panel into the product edit page for an authorised admin', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => $context->channel->code]))
        ->assertOk()
        // The panel id, not its heading: the fixture's attribute group carries the same label.
        ->assertSee('id="passport-panel"', false);
});

it('does not render the panel for an admin without view permission', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('custom', ['catalog.products', 'catalog.products.edit']);

    $this->get(route('admin.catalog.products.edit', ['id' => $product->id, 'channel' => $context->channel->code]))
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

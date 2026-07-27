<?php

use Webkul\Publication\Models\Publication;

it('renders the panel into the product edit page for an authorised admin', function (): void {
    [$product] = $this->productWithSecretAndDppAttributes();

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertSee(trans('passport::app.catalog.products.edit.passport.title'));
});

it('does not render the panel for an admin without view permission', function (): void {
    [$product] = $this->productWithSecretAndDppAttributes();

    $this->loginWithPermissions('custom', ['dashboard']);

    $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertDontSee(trans('passport::app.catalog.products.edit.passport.title'));
});

it('shows per-locale passport status with missing field counts', function (): void {
    [$product, $channel, $incomplete, $complete] = $this->productWithTwoDppLocales();

    $this->loginWithPermissions('all');

    $this->getJson(route('admin.catalog.products.passport.show', $product))
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

    // QUEUE_CONNECTION=sync in tests, so the dispatched job already ran
    // inline by the time the request above returns.
    expect(Publication::where('product_id', $product->id)->exists())->toBeFalse();
});

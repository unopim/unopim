<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationVersion;
use Webkul\Publication\Models\PublicationViewStat;

it('renders the current product data behind a not-published banner with zero side effects', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.products.passport.preview', [
        'product'    => $product->id,
        'channel_id' => $context->channel->id,
        'locale_id'  => $context->locale->id,
    ]))
        ->assertOk()
        ->assertSee('Recycled cotton, 80%')
        ->assertSee(trans('passport::app.public.preview.banner'))
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    // No Publication, no immutable version, and no counted view: preview is read-only.
    $publications = Publication::query()->where('product_id', $product->id)->pluck('id');

    expect($publications)->toBeEmpty()
        ->and(PublicationVersion::query()->whereIn('publication_id', $publications)->count())->toBe(0)
        ->and(PublicationViewStat::query()->whereIn('publication_id', $publications)->count())->toBe(0);
});

it('lists document fields as pending without writing to the asset disk', function (): void {
    Storage::fake(config('filesystems.default'));
    Storage::fake(config('publication.asset_disk'));

    [$product, $context] = $this->productWithSecretAndDppAttributes(withDocument: true);

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.products.passport.preview', [
        'product'    => $product->id,
        'channel_id' => $context->channel->id,
        'locale_id'  => $context->locale->id,
    ]))
        ->assertOk()
        ->assertSee(trans('passport::app.public.preview.document-pending'));

    expect(Storage::disk(config('publication.asset_disk'))->allFiles())->toBeEmpty();
});

it('forbids preview for an admin without passport view permission', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->loginWithPermissions('custom', ['dashboard']);

    $this->get(route('admin.catalog.products.passport.preview', [
        'product'    => $product->id,
        'channel_id' => $context->channel->id,
        'locale_id'  => $context->locale->id,
    ]))->assertForbidden();
});

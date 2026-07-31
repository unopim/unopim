<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\ProductProxy;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;

/**
 * Both scopes are written: an unscoped row left in the catalogue would otherwise
 * decide the outcome, since a channel without its own row falls back to it.
 */
function setChannelPassportConfig(string $channelCode, bool $enabled, bool $autoPublish): void
{
    foreach ([
        'catalog.product_passport.settings.enabled'      => $enabled,
        'catalog.product_passport.settings.auto_publish' => $autoPublish,
    ] as $code => $value) {
        foreach ([$channelCode, null] as $scope) {
            CoreConfig::query()->updateOrCreate(
                ['code' => $code, 'channel_code' => $scope, 'locale_code' => null],
                ['value' => $value ? '1' : '0'],
            );
        }
    }
}

it('dispatches a publish job when auto_publish is on for a dpp-family product', function (): void {
    Queue::fake();

    [$product, $context] = $this->productWithSecretAndDppAttributes();

    setChannelPassportConfig($context->channel->code, enabled: true, autoPublish: true);

    Event::dispatch('catalog.product.update.after', $product);

    Queue::assertPushed(
        PublishPassportForProductChannelJob::class,
        fn (PublishPassportForProductChannelJob $job): bool => $job->uniqueId() === "{$product->id}:{$context->channel->id}:dpp",
    );
});

it('dispatches nothing when auto_publish is off', function (): void {
    Queue::fake();

    [$product, $context] = $this->productWithSecretAndDppAttributes();

    setChannelPassportConfig($context->channel->code, enabled: true, autoPublish: false);

    Event::dispatch('catalog.product.update.after', $product);

    Queue::assertNotPushed(PublishPassportForProductChannelJob::class);
});

it('dispatches nothing for a product whose family carries no passport template', function (): void {
    Queue::fake();

    $channel = ChannelProxy::factory()->create();
    $channel->locales()->first() ?: $channel->locales()->attach(Locale::factory()->create());

    setChannelPassportConfig($channel->code, enabled: true, autoPublish: true);

    $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();

    $product = ProductProxy::factory()->create(['attribute_family_id' => $family->id]);

    Event::dispatch('catalog.product.update.after', $product);

    Queue::assertNotPushed(PublishPassportForProductChannelJob::class);
});

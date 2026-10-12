<?php

use Illuminate\Support\Facades\Bus;
use Webkul\Core\Models\CoreConfig;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\PublicationPublishAttempt;

/**
 * `general.publication.settings.enabled` is not declared `channel_based`, so
 * `getConfigData()` resolves the first row for the code whatever channel is
 * asked for. Every row has to be cleared for the switch to read as off.
 */
function disablePublicTier(string $channelCode): void
{
    CoreConfig::query()->where('code', 'general.publication.settings.enabled')->delete();

    CoreConfig::query()->create([
        'code'         => 'general.publication.settings.enabled',
        'channel_code' => $channelCode,
        'locale_code'  => null,
        'value'        => '0',
    ]);
}

it('refuses a publish while the channel public tier is off', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    disablePublicTier($context->channel->code);

    $this->loginWithPermissions('all');

    Bus::fake();

    $this->postJson(route('admin.catalog.passports.publish', $product->id), [
        'channel_id' => $context->channel->id,
        'locale_ids' => [$context->locale->id],
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', trans('passport::app.publications.public-access-disabled'));

    Bus::assertNothingDispatched();

    expect(PublicationPublishAttempt::query()->count())->toBe(0);
});

it('queues the publish once the channel public tier is on', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $this->enablePublicTier($context->channel->code);

    $this->loginWithPermissions('all');

    Bus::fake();

    $this->postJson(route('admin.catalog.passports.publish', $product->id), [
        'channel_id' => $context->channel->id,
        'locale_ids' => [$context->locale->id],
    ])->assertOk();

    Bus::assertDispatched(PublishPassportForProductChannelJob::class);
});

it('refuses a bulk publish whose channels all have the public tier off', function (): void {
    $publication = $this->publishedPassportFixture()->publication;

    disablePublicTier($publication->channel->code);

    $this->loginWithPermissions('all');

    Bus::fake();

    $this->postJson(route('admin.catalog.passports.bulk-publish'), ['indices' => [$publication->id]])
        ->assertStatus(422)
        ->assertJsonPath('message', trans('passport::app.publications.public-access-disabled'));

    Bus::assertNothingDispatched();
});

it('refuses a republish while the channel public tier is off', function (): void {
    $version = $this->publishedPassportFixture();

    disablePublicTier($version->publication->channel->code);

    $this->loginWithPermissions('all');

    $this->postJson(route('admin.catalog.passports.republish', $version->publication->id), [
        'version_id' => $version->id,
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', trans('passport::app.publications.public-access-disabled'));
});

it('warns on the product panel instead of offering a publish button', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    disablePublicTier($context->channel->code);

    $this->loginWithPermissions('all');

    $content = $this->get(route('admin.catalog.products.edit', [
        'id'      => $product->id,
        'channel' => $context->channel->code,
        'locale'  => $context->locale->code,
    ]))->assertOk()->getContent();

    expect($content)->toContain(trans('passport::app.catalog.products.edit.passport.public-access-badge'))
        ->and(preg_match('/passport-publish-all-btn[^>]*\bdisabled\b/s', $content))->toBe(1);
});

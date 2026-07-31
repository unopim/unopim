<?php

use Illuminate\Support\Facades\Bus;
use Webkul\Publication\Enums\PublishAttemptStatus;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\PublicationPublishAttempt;
use Webkul\Publication\Services\Publisher;

it('hands the panel an attempt to follow when a publish is queued', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);
    $this->enablePublicTier($context->channel->code);

    $this->loginWithPermissions('all');

    Bus::fake();

    $response = $this->postJson(route('admin.catalog.passports.publish', $product->id), [
        'channel_id' => $context->channel->id,
        'locale_ids' => [$context->locale->id],
    ])->assertOk();

    $attempt = PublicationPublishAttempt::query()->latest('id')->first();

    expect($attempt)->not->toBeNull()
        ->and($attempt->status)->toBe(PublishAttemptStatus::Queued)
        ->and($attempt->locale_ids)->toBe([$context->locale->id]);

    $response->assertJsonPath('attempt_url', route('admin.catalog.passports.publish_attempt', $attempt->id));

    Bus::assertDispatched(PublishPassportForProductChannelJob::class);
});

it('reports the published version once the job has run', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->enablePassportPublishing($context->channel->code);

    $attempt = PublicationPublishAttempt::query()->create([
        'product_id' => $product->id,
        'channel_id' => $context->channel->id,
        'type'       => 'dpp',
        'locale_ids' => [$context->locale->id],
        'status'     => PublishAttemptStatus::Queued,
    ]);

    (new PublishPassportForProductChannelJob(
        $product->id,
        $context->channel->id,
        'dpp',
        [$context->locale->id],
        null,
        $attempt->id,
    ))->handle(resolve(Publisher::class));

    $this->loginWithPermissions('all');

    $this->getJson(route('admin.catalog.passports.publish_attempt', $attempt->id))
        ->assertOk()
        ->assertJsonPath('status', PublishAttemptStatus::Completed->value)
        ->assertJsonPath('settled', true)
        ->assertJsonPath('refused', false)
        ->assertJsonPath('locales.0.version', 1)
        ->assertJsonPath('locales.0.published', true);
});

it('settles an attempt whose payload was already current without claiming a new version', function (): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    $attempt = PublicationPublishAttempt::query()->create([
        'product_id' => $publication->product_id,
        'channel_id' => $publication->channel_id,
        'type'       => 'dpp',
        'locale_ids' => [$version->locale_id],
        'status'     => PublishAttemptStatus::Queued,
    ]);

    (new PublishPassportForProductChannelJob(
        $publication->product_id,
        $publication->channel_id,
        'dpp',
        [$version->locale_id],
        null,
        $attempt->id,
    ))->handle(resolve(Publisher::class));

    $this->loginWithPermissions('all');

    $this->getJson(route('admin.catalog.passports.publish_attempt', $attempt->id))
        ->assertOk()
        ->assertJsonPath('settled', true)
        ->assertJsonPath('locales.0.published', false)
        ->assertJsonPath('locales.0.version', 1);
});

it('marks the attempt refused when the passport is withdrawn', function (): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    resolve(Publisher::class)->withdraw($publication);

    $attempt = PublicationPublishAttempt::query()->create([
        'product_id' => $publication->product_id,
        'channel_id' => $publication->channel_id,
        'type'       => 'dpp',
        'locale_ids' => [$version->locale_id],
        'status'     => PublishAttemptStatus::Queued,
    ]);

    (new PublishPassportForProductChannelJob(
        $publication->product_id,
        $publication->channel_id,
        'dpp',
        [$version->locale_id],
        null,
        $attempt->id,
    ))->handle(resolve(Publisher::class));

    $this->loginWithPermissions('all');

    $this->getJson(route('admin.catalog.passports.publish_attempt', $attempt->id))
        ->assertOk()
        ->assertJsonPath('settled', true)
        ->assertJsonPath('refused', true);
});

it('marks the attempt failed when the job dies', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $attempt = PublicationPublishAttempt::query()->create([
        'product_id' => $product->id,
        'channel_id' => $context->channel->id,
        'type'       => 'dpp',
        'locale_ids' => [$context->locale->id],
        'status'     => PublishAttemptStatus::Queued,
    ]);

    (new PublishPassportForProductChannelJob(
        $product->id,
        $context->channel->id,
        'dpp',
        [$context->locale->id],
        null,
        $attempt->id,
    ))->failed(new RuntimeException('worker died'));

    expect($attempt->refresh()->status)->toBe(PublishAttemptStatus::Failed);
});

it('keeps the attempt endpoint behind the passport view permission', function (): void {
    $version = $this->publishedPassportFixture();

    $attempt = PublicationPublishAttempt::query()->create([
        'product_id' => $version->publication->product_id,
        'channel_id' => $version->publication->channel_id,
        'type'       => 'dpp',
        'locale_ids' => [$version->locale_id],
        'status'     => PublishAttemptStatus::Queued,
    ]);

    $this->loginWithPermissions('custom', ['dashboard']);

    $this->getJson(route('admin.catalog.passports.publish_attempt', $attempt->id))
        ->assertForbidden();
});

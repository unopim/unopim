<?php

use Illuminate\Support\Facades\Queue;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\ProductProxy;
use Webkul\ProductPassport\Jobs\BulkPublishPassportsJob;
use Webkul\Publication\Jobs\PublishPassportForProductChannelJob;
use Webkul\Publication\Models\Publication;

function makePassportPublication(bool $withLocales = true): Publication
{
    $channel = ChannelProxy::factory()->create();

    if ($withLocales) {
        if ($channel->locales()->doesntExist()) {
            $channel->locales()->attach(Locale::factory()->create());
        }
    } else {
        $channel->locales()->detach();
    }

    return Publication::factory()->create([
        'product_id' => ProductProxy::factory()->create()->id,
        'channel_id' => $channel->id,
        'type'       => 'dpp',
    ]);
}

it('fans out one publish job per publication', function (): void {
    Queue::fake();

    $publications = collect([makePassportPublication(), makePassportPublication()]);

    (new BulkPublishPassportsJob($publications->pluck('id')->all(), auth()->guard('admin')->id()))->handle();

    Queue::assertPushed(PublishPassportForProductChannelJob::class, 2);
});

it('skips publications whose channel has no locales', function (): void {
    Queue::fake();

    $withLocales = makePassportPublication(withLocales: true);
    $withoutLocales = makePassportPublication(withLocales: false);

    (new BulkPublishPassportsJob([$withLocales->id, $withoutLocales->id], null))->handle();

    Queue::assertPushed(PublishPassportForProductChannelJob::class, 1);
});

it('rejects bulk publish without permission', function (): void {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->post(route('admin.catalog.passports.bulk-publish'), ['indices' => [1, 2]])
        ->assertForbidden();
});

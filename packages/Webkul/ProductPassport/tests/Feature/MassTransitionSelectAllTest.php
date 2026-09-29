<?php

use Illuminate\Support\Facades\Bus;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\ProductPassport\Jobs\BulkTransitionPassportsJob;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Models\Publication;

function createPublicationsWithSkuPrefix(string $prefix, int $count, object $channel): array
{
    return collect(range(1, $count))
        ->map(fn (int $index) => Publication::factory()->create([
            'product_id' => ProductProxy::factory()->create(['sku' => $prefix.$index])->id,
            'channel_id' => $channel->id,
            'type'       => 'dpp',
            'status'     => PublicationStatus::Published,
        ])->id)
        ->all();
}

beforeEach(function (): void {
    $this->channel = ChannelProxy::factory()->create();

    $this->enablePassportPublishing($this->channel->code);

    $this->loginWithPermissions('all');
});

it('withdraws every passport matching the filters when all matching are selected', function (): void {
    $matching = createPublicationsWithSkuPrefix('SELTR-MATCH-', 4, $this->channel);
    $other = createPublicationsWithSkuPrefix('SELTR-OTHER-', 2, $this->channel);

    $this->postJson(route('admin.catalog.passports.mass_transition'), [
        'select_all' => 1,
        'filters'    => ['sku' => ['SELTR-MATCH-']],
        'value'      => PublicationStatus::Withdrawn->value,
    ])->assertOk();

    expect(Publication::whereIn('id', $matching)->where('status', PublicationStatus::Withdrawn)->count())->toBe(4)
        ->and(Publication::whereIn('id', $other)->where('status', PublicationStatus::Published)->count())->toBe(2);
});

it('withdraws only the given passports when an id list is sent', function (): void {
    [$first, $second, $third] = createPublicationsWithSkuPrefix('SELIDX-', 3, $this->channel);

    $this->postJson(route('admin.catalog.passports.mass_transition'), [
        'indices' => [$first, $second],
        'value'   => PublicationStatus::Withdrawn->value,
    ])->assertOk();

    expect(Publication::whereIn('id', [$first, $second])->where('status', PublicationStatus::Withdrawn)->count())->toBe(2)
        ->and(Publication::find($third)->status)->toBe(PublicationStatus::Published);
});

it('dispatches the transition in bounded chunks and reports the selection size', function (): void {
    Bus::fake();

    createPublicationsWithSkuPrefix('SELCH-', 3, $this->channel);

    $this->postJson(route('admin.catalog.passports.mass_transition'), [
        'select_all' => 1,
        'filters'    => ['sku' => ['SELCH-']],
        'value'      => PublicationStatus::Withdrawn->value,
    ])->assertOk()
        ->assertJsonPath('message', trans('passport::app.publications.mass-withdraw-queued', ['count' => 3]));

    Bus::assertDispatchedTimes(BulkTransitionPassportsJob::class, 1);
});

it('still requires an id list when all matching are not selected', function (): void {
    $this->postJson(route('admin.catalog.passports.mass_transition'), ['value' => PublicationStatus::Withdrawn->value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('indices');
});

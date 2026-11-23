<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Completeness\Jobs\ProductCompletenessJob;
use Webkul\Completeness\Models\CompletenessSettingProxy;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\ProductProxy;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->channel = ChannelProxy::first();

    $attribute = AttributeProxy::factory()->create([
        'type'              => 'text',
        'is_required'       => 1,
        'value_per_channel' => 1,
        'value_per_locale'  => 0,
    ]);

    $familyId = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create()->id;

    $this->product = ProductProxy::factory()->create([
        'attribute_family_id' => $familyId,
        'values'              => [
            'channel_specific' => [
                $this->channel->code => [
                    $attribute->code => 'Some Value',
                ],
            ],
        ],
    ]);

    CompletenessSettingProxy::factory()->create([
        'family_id'    => $familyId,
        'attribute_id' => $attribute->id,
        'channel_id'   => $this->channel->id,
    ]);
});

function completenessRows(int $productId): Collection
{
    return DB::table('product_completeness')->where('product_id', $productId)->get();
}

it('stamps created_at and updated_at on new completeness rows', function () {
    ProductCompletenessJob::dispatchSync([$this->product->id]);

    $rows = completenessRows($this->product->id);

    expect($rows)->not->toBeEmpty();

    $rows->each(function (object $row): void {
        expect($row->created_at)->not->toBeNull()
            ->and($row->updated_at)->not->toBeNull();
    });
});

it('keeps updated_at when the score has not changed', function () {
    ProductCompletenessJob::dispatchSync([$this->product->id]);

    $past = Carbon::now()->subDays(30)->startOfSecond();

    DB::table('product_completeness')->where('product_id', $this->product->id)->update(['updated_at' => $past]);

    ProductCompletenessJob::dispatchSync([$this->product->id]);

    completenessRows($this->product->id)->each(function (object $row) use ($past): void {
        expect(Carbon::parse($row->updated_at)->equalTo($past))->toBeTrue();
    });
});

it('moves updated_at forward when the score changes', function () {
    ProductCompletenessJob::dispatchSync([$this->product->id]);

    $past = Carbon::now()->subDays(30)->startOfSecond();

    DB::table('product_completeness')
        ->where('product_id', $this->product->id)
        ->update(['score' => 0, 'missing_count' => 1, 'updated_at' => $past]);

    ProductCompletenessJob::dispatchSync([$this->product->id]);

    completenessRows($this->product->id)->each(function (object $row) use ($past): void {
        expect((int) $row->score)->toBe(100)
            ->and(Carbon::parse($row->updated_at)->greaterThan($past))->toBeTrue();
    });
});

it('counts a freshly scored product as enriched this week on the dashboard', function () {
    ProductCompletenessJob::dispatchSync([$this->product->id]);

    Cache::forget('dashboard.product_stats');

    expect(app(Dashboard::class)->getProductStats()['enrichedThisWeek'])->toBeGreaterThanOrEqual(1);
});

it('announces the products whose completeness was calculated', function () {
    Event::fake(['completeness.product.calculate.after']);

    ProductCompletenessJob::dispatchSync([$this->product->id]);

    Event::assertDispatched('completeness.product.calculate.after');
});

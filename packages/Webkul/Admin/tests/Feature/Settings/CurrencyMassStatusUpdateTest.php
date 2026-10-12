<?php

use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;

use function Pest\Laravel\postJson;

$usedCurrency = function (): Currency {
    $category = Category::factory()->create(['parent_id' => null]);

    $channel = Channel::factory()->create(['root_category_id' => $category->id]);

    $currency = Currency::factory()->create(['status' => 1]);

    $channel->currencies()->attach($currency->id);

    return $currency;
};

$massUpdate = fn (array $ids, int $value) => postJson(
    route('admin.settings.currencies.mass_update'),
    ['indices' => $ids, 'value' => $value]
);

it('does not disable a currency that a channel still uses', function () use ($usedCurrency, $massUpdate) {
    $this->loginAsAdmin();

    $currency = $usedCurrency();

    $massUpdate([$currency->id], 0);

    expect((int) $currency->fresh()->status)->toBe(1);
});

it('reports that a channel linked currency could not be disabled', function () use ($usedCurrency, $massUpdate) {
    $this->loginAsAdmin();

    $currency = $usedCurrency();

    $response = $massUpdate([$currency->id], 0);

    expect($response->json('message'))
        ->not->toBe(trans('admin::app.settings.currencies.index.update-success'));
});

it('fails the request when no currency could be disabled at all', function () use ($usedCurrency, $massUpdate) {
    $this->loginAsAdmin();

    $currency = $usedCurrency();

    $massUpdate([$currency->id], 0)->assertStatus(422);
});

it('disables a currency that no channel uses', function () use ($massUpdate) {
    $this->loginAsAdmin();

    $currency = Currency::factory()->create(['status' => 1]);

    $massUpdate([$currency->id], 0)
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.settings.currencies.index.update-success'));

    expect((int) $currency->fresh()->status)->toBe(0);
});

it('enables a channel linked currency without skipping it', function () use ($usedCurrency, $massUpdate) {
    $this->loginAsAdmin();

    $currency = $usedCurrency();

    $currency->update(['status' => 0]);

    $massUpdate([$currency->id], 1)
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.settings.currencies.index.update-success'));

    expect((int) $currency->fresh()->status)->toBe(1);
});

it('reports a partial result when only some selected currencies could be disabled', function () use ($usedCurrency, $massUpdate) {
    $this->loginAsAdmin();

    $linked = $usedCurrency();

    $free = Currency::factory()->create(['status' => 1]);

    $response = $massUpdate([$linked->id, $free->id], 0)->assertOk();

    expect((int) $free->fresh()->status)->toBe(0)
        ->and((int) $linked->fresh()->status)->toBe(1)
        ->and($response->json('message'))
        ->not->toBe(trans('admin::app.settings.currencies.index.update-success'));
});

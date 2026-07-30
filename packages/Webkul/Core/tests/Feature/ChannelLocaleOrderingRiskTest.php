<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\ChannelRepository;

function riskChannelRepository(): ChannelRepository
{
    return app(ChannelRepository::class);
}

function riskLocaleIds(array $codes): array
{
    return collect($codes)
        ->map(fn (string $code): int => Locale::firstOrCreate(
            ['code' => $code],
            ['status' => 1, 'direction' => 'ltr']
        )->id)
        ->all();
}

function riskInsertPivotRowsInOrder(Channel $channel, array $localeIds): void
{
    DB::table('channel_locales')->where('channel_id', $channel->id)->delete();

    foreach ($localeIds as $localeId) {
        DB::table('channel_locales')->insert([
            'channel_id' => $channel->id,
            'locale_id'  => $localeId,
        ]);
    }
}

function riskChannelWithLocalesInsertedInOrder(array $codes): Channel
{
    $channel = Channel::factory()->create();

    riskInsertPivotRowsInOrder($channel, riskLocaleIds($codes));

    return $channel->fresh();
}

/**
 * `channel_locales` is InnoDB with PRIMARY KEY (channel_id, locale_id), so an unordered read comes
 * back in locale_id order, not insertion order. The only arrangement that tells the two apart is a
 * pair whose id order is the inverse of its code order.
 */
function riskLocalesWithIdOrderInverseToCodeOrder(): array
{
    $suffix = strtoupper(fake()->unique()->lexify('??'));

    $lowIdHighCode = Locale::create(['code' => 'zz_'.$suffix, 'status' => 1, 'direction' => 'ltr']);
    $highIdLowCode = Locale::create(['code' => 'aa_'.$suffix, 'status' => 1, 'direction' => 'ltr']);

    expect($lowIdHighCode->id)->toBeLessThan($highIdLowCode->id);

    return [$lowIdHighCode, $highIdLowCode];
}

it('returns channel locales sorted by code even when that inverts their id order', function () {
    [$lowIdHighCode, $highIdLowCode] = riskLocalesWithIdOrderInverseToCodeOrder();

    $channel = Channel::factory()->create();

    riskInsertPivotRowsInOrder($channel, [$lowIdHighCode->id, $highIdLowCode->id]);

    expect($channel->fresh()->locales->pluck('code')->all())
        ->toBe([$highIdLowCode->code, $lowIdHighCode->code]);
});

it('answers the same whichever order the pivot rows were physically inserted in', function () {
    [$lowIdHighCode, $highIdLowCode] = riskLocalesWithIdOrderInverseToCodeOrder();

    $forward = Channel::factory()->create();
    $reverse = Channel::factory()->create();

    riskInsertPivotRowsInOrder($forward, [$lowIdHighCode->id, $highIdLowCode->id]);
    riskInsertPivotRowsInOrder($reverse, [$highIdLowCode->id, $lowIdHighCode->id]);

    expect($forward->fresh()->locales->pluck('code')->all())
        ->toBe($reverse->fresh()->locales->pluck('code')->all());
});

it('orders the eager loaded locales relation without an ambiguous column error', function () {
    riskChannelWithLocalesInsertedInOrder(['fr_FR', 'de_DE', 'en_US']);

    $channels = Channel::query()->with('locales')->get();

    expect($channels)->not->toBeEmpty();

    foreach ($channels as $channel) {
        $codes = $channel->locales->pluck('code')->all();

        expect($codes)->toBe(collect($codes)->sort()->values()->all());
    }
});

it('resolves whereHas and joined queries on the locales relation without an ambiguous column error', function () {
    riskChannelWithLocalesInsertedInOrder(['de_DE', 'en_US']);

    $viaWhereHas = Channel::query()
        ->whereHas('locales', fn ($query) => $query->where('code', 'en_US'))
        ->count();

    $viaRelationJoin = Channel::query()
        ->join('channel_locales', 'channel_locales.channel_id', '=', 'channels.id')
        ->join('locales', 'locales.id', '=', 'channel_locales.locale_id')
        ->where('locales.code', 'en_US')
        ->count('channels.id');

    $viaPluck = Channel::query()->first()->locales()->pluck('code')->all();

    expect($viaWhereHas)->toBeGreaterThan(0)
        ->and($viaRelationJoin)->toBeGreaterThan(0)
        ->and($viaPluck)->toBe(collect($viaPluck)->sort()->values()->all());
});

it('does not record a channel history entry when a save leaves the locale set unchanged', function () {
    $localeIds = riskLocaleIds(['de_DE', 'en_US', 'fr_FR']);

    $channel = Channel::factory()->create();

    riskInsertPivotRowsInOrder($channel, array_reverse($localeIds));

    $currencyId = Currency::query()->value('id');

    $channel->currencies()->sync([$currencyId]);

    $auditsBefore = $channel->audits()->count();

    riskChannelRepository()->update([
        'code'       => $channel->code,
        'locales'    => array_reverse($localeIds),
        'currencies' => [$currencyId],
    ], $channel->id);

    expect($channel->audits()->count())->toBe($auditsBefore);
});

function riskCaptureLocaleSyncPayload(callable $work): array
{
    $captured = [];

    Event::listen('core.model.proxy.sync.*', function ($event, $data) use (&$captured): void {
        if ($event !== 'core.model.proxy.sync.locales') {
            return;
        }

        $captured = [
            'old' => $data['old_values'],
            'new' => $data['new_values'],
        ];
    });

    $work();

    return $captured;
}

it('feeds the history diff identical old and new lists when only the pivot order differs', function () {
    $localeIds = riskLocaleIds(['de_DE', 'en_US', 'fr_FR']);

    $channel = Channel::factory()->create();

    riskInsertPivotRowsInOrder($channel, array_reverse($localeIds));

    $currencyId = Currency::query()->value('id');

    $channel->currencies()->sync([$currencyId]);

    $payload = riskCaptureLocaleSyncPayload(fn () => riskChannelRepository()->update([
        'code'       => $channel->code,
        'locales'    => $localeIds,
        'currencies' => [$currencyId],
    ], $channel->id));

    expect($payload['old'])->toBe(['de_DE', 'en_US', 'fr_FR'])
        ->and($payload['new'])->toBe($payload['old']);
});

it('feeds the history diff differing sorted lists when the locale set genuinely changes', function () {
    $localeIds = riskLocaleIds(['de_DE', 'en_US', 'fr_FR']);

    $channel = Channel::factory()->create();

    riskInsertPivotRowsInOrder($channel, [$localeIds[2], $localeIds[0]]);

    $currencyId = Currency::query()->value('id');

    $channel->currencies()->sync([$currencyId]);

    $payload = riskCaptureLocaleSyncPayload(fn () => riskChannelRepository()->update([
        'code'       => $channel->code,
        'locales'    => array_reverse($localeIds),
        'currencies' => [$currencyId],
    ], $channel->id));

    expect($payload['old'])->toBe(['de_DE', 'fr_FR'])
        ->and($payload['new'])->toBe(['de_DE', 'en_US', 'fr_FR'])
        ->and($payload['new'])->not->toBe($payload['old']);
});

<?php

use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\ChannelRepository;

function auditProbeRepository(): ChannelRepository
{
    return app(ChannelRepository::class);
}

function auditProbeLocaleIds(array $codes): array
{
    return Locale::whereIn('code', $codes)->pluck('id')->all();
}

function auditProbeCurrencyIds(array $codes): array
{
    return Currency::whereIn('code', $codes)->pluck('id')->all();
}

function auditProbeAuditsFor(Channel $channel): array
{
    return DB::table('audits')
        ->where('auditable_type', $channel::class)
        ->where('auditable_id', $channel->id)
        ->orderByDesc('id')
        ->get()
        ->all();
}

function auditProbeUpdateChannel(Channel $channel, array $locales, array $currencies): void
{
    auditProbeRepository()->update([
        'code'       => $channel->code,
        'locales'    => auditProbeLocaleIds($locales),
        'currencies' => auditProbeCurrencyIds($currencies),
    ], $channel->id);
}

beforeEach(function () {
    $this->channel = auditProbeRepository()->create([
        'code'       => 'audit_probe_'.uniqid(),
        'locales'    => auditProbeLocaleIds(['en_US']),
        'currencies' => auditProbeCurrencyIds(['USD']),
        'en_US'      => ['name' => 'Audit Probe'],
    ]);

    DB::table('audits')
        ->where('auditable_type', Channel::class)
        ->where('auditable_id', $this->channel->id)
        ->delete();
});

it('records an audit when only the locales change', function () {
    auditProbeUpdateChannel($this->channel, ['en_US', 'fr_FR'], ['USD']);

    $audits = auditProbeAuditsFor($this->channel);

    expect($audits)->not->toBeEmpty();

    $new = json_decode($audits[0]->new_values, true);

    expect($new)->toHaveKey('common')
        ->and($new['common'])->toHaveKey('Locales')
        ->and($new['common']['Locales'])->toContain('fr_FR');
});

it('records an audit when only the currencies change', function () {
    auditProbeUpdateChannel($this->channel, ['en_US'], ['USD', 'EUR']);

    $audits = auditProbeAuditsFor($this->channel);

    expect($audits)->not->toBeEmpty();

    $new = json_decode($audits[0]->new_values, true);

    expect($new['common'])->toHaveKey('Currencies')
        ->and($new['common']['Currencies'])->toContain('EUR');
});

it('keeps the removed value on the old side', function () {
    auditProbeUpdateChannel($this->channel, ['en_US', 'fr_FR'], ['USD']);

    DB::table('audits')
        ->where('auditable_type', Channel::class)
        ->where('auditable_id', $this->channel->id)
        ->delete();

    auditProbeUpdateChannel($this->channel, ['en_US'], ['USD']);

    $audits = auditProbeAuditsFor($this->channel);

    expect($audits)->not->toBeEmpty();

    $audit = $audits[0];

    expect(json_decode($audit->old_values, true)['common']['Locales'])->toContain('fr_FR')
        ->and(json_decode($audit->new_values, true)['common']['Locales'])->not->toContain('fr_FR');
});

it('writes nothing when neither list actually changes', function () {
    auditProbeUpdateChannel($this->channel, ['en_US'], ['USD']);

    expect(auditProbeAuditsFor($this->channel))->toBeEmpty();
});

it('records both lists in one audit when both change together', function () {
    auditProbeUpdateChannel($this->channel, ['en_US', 'fr_FR'], ['USD', 'EUR']);

    $audits = auditProbeAuditsFor($this->channel);

    $common = collect($audits)
        ->map(fn ($audit): array => json_decode($audit->new_values, true)['common'] ?? [])
        ->reduce(fn (array $carry, array $item): array => $carry + $item, []);

    expect($common)->toHaveKeys(['Locales', 'Currencies']);
});

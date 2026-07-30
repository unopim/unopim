<?php

use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\CoreConfig;

function primeConfig(string $key, string $value): void
{
    CoreConfig::query()->updateOrCreate(
        ['code' => $key, 'channel_code' => null, 'locale_code' => null],
        ['value' => $value],
    );
}

it('serves a config write made through the model to later reads in the same request', function () {
    $key = 'general.magic_ai.agentic_pim.enabled';

    primeConfig($key, '1');

    expect(core()->getConfigData($key))->toBe('1');

    primeConfig($key, '0');

    expect(core()->getConfigData($key))->toBe('0');
});

it('picks up a raw database config write on the next request', function () {
    $key = 'general.magic_ai.agentic_pim.enabled';

    primeConfig($key, '1');

    expect(core()->getConfigData($key))->toBe('1');

    DB::table('core_config')->where('code', $key)->update(['value' => '0']);

    app()->forgetScopedInstances();

    expect(core()->getConfigData($key))->toBe('0');
});

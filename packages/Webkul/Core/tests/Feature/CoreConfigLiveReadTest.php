<?php

use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\CoreConfig;

it('reads core config live and never serves a stale cached value after a change', function () {
    $key = 'general.magic_ai.agentic_pim.enabled';

    CoreConfig::query()->updateOrCreate(
        ['code' => $key, 'channel_code' => null, 'locale_code' => null],
        ['value' => '1'],
    );

    // Prime the read path (this is what populated the stale cache before the fix).
    expect(core()->getConfigData($key))->toBe('1');

    // Change the value directly in the DB, bypassing the repository's own write
    // (so no repository cache-clean event can fire). A cached read would still
    // return the old '1' here — the bug. A live read must reflect '0'.
    DB::table('core_config')->where('code', $key)->update(['value' => '0']);

    expect(core()->getConfigData($key))->toBe('0');
});

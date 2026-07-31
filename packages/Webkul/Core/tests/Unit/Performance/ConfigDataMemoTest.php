<?php

use Illuminate\Support\Facades\DB;

/**
 * Guards against F5 from the product detail performance analysis.
 *
 * getConfigData() runs a fresh core_config lookup on every call, with no
 * request-scoped cache. The product edit page calls it many times per request
 * (once per attribute group for the Magic AI flag, once per image/file
 * attribute for validations, plus layout). Repeat calls for the same
 * field/channel/locale must not re-query.
 */
it('does not re-query core_config for a repeated getConfigData call (F5)', function () {
    core()->getConfigData('general.magic_ai.translation.enabled', 'default', 'en_US');

    DB::flushQueryLog();
    DB::enableQueryLog();

    core()->getConfigData('general.magic_ai.translation.enabled', 'default', 'en_US');

    expect(DB::getQueryLog())->toHaveCount(0);
});

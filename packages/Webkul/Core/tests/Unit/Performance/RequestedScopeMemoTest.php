<?php

use Illuminate\Support\Facades\DB;

/**
 * Regression guard for F4 from the product detail performance analysis.
 *
 * The analysis predicted getRequestedChannel() / getRequestedLocale() re-query
 * on every call when ?channel= / ?locale= is present. In practice the repository
 * layer (prettus CacheableRepository) already caches findOneByField, so repeat
 * calls do not hit the database. These tests lock that behaviour in — if repo
 * caching is ever disabled, F4 becomes real and these fail.
 */
it('does not re-query the channel for a repeated getRequestedChannel call (F4)', function () {
    request()->query->set('channel', 'default');

    core()->getRequestedChannel();

    DB::flushQueryLog();
    DB::enableQueryLog();

    core()->getRequestedChannel();

    expect(DB::getQueryLog())->toHaveCount(0);
});

it('does not re-query the locale for a repeated getRequestedLocale call (F4)', function () {
    request()->query->set('locale', 'en_US');

    core()->getRequestedLocale();

    DB::flushQueryLog();
    DB::enableQueryLog();

    core()->getRequestedLocale();

    expect(DB::getQueryLog())->toHaveCount(0);
});

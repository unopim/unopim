<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\DataTransfer\Validators\JobInstances\Export\ProductJobValidator;

function seedValidatorChannel(): void
{
    $enUS = Locale::updateOrCreate(['code' => 'en_US'], ['status' => 1]);
    $frFR = Locale::updateOrCreate(['code' => 'fr_FR'], ['status' => 1]);
    Locale::updateOrCreate(['code' => 'de_DE'], ['status' => 1]);

    $usd = Currency::updateOrCreate(['code' => 'USD'], ['status' => 1]);
    Currency::updateOrCreate(['code' => 'GBP'], ['status' => 1]);

    $web = Channel::factory()->create(['code' => 'web']);
    $web->locales()->sync([$enUS->id, $frFR->id]);
    $web->currencies()->sync([$usd->id]);

    Cache::flush();
}

it('accepts locales and currencies that belong to the selected channel', function () {
    seedValidatorChannel();

    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format' => 'Csv',
            'channels'    => ['web'],
            'locales'     => ['en_US', 'fr_FR'],
            'currencies'  => ['USD'],
        ],
    ]);
})->throwsNoExceptions();

it('rejects a locale that is not in the selected channel', function () {
    seedValidatorChannel();

    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format' => 'Csv',
            'channels'    => ['web'],
            'locales'     => ['en_US', 'de_DE'],
        ],
    ]);
})->throws(ValidationException::class);

it('rejects a currency that is not in the selected channel', function () {
    seedValidatorChannel();

    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format' => 'Csv',
            'channels'    => ['web'],
            'currencies'  => ['GBP'],
        ],
    ]);
})->throws(ValidationException::class);

it('allows any locale and currency when no channel is selected', function () {
    seedValidatorChannel();

    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format' => 'Csv',
            'locales'     => ['de_DE'],
            'currencies'  => ['GBP'],
        ],
    ]);
})->throwsNoExceptions();

it('accepts every allowed status filter value', function (string $status) {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format' => 'Csv',
            'status'      => $status,
        ],
    ]);
})->with(['enable', 'disable', 'all'])->throwsNoExceptions();

it('rejects an unknown status filter value', function () {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format' => 'Csv',
            'status'      => 'archived',
        ],
    ]);
})->throws(ValidationException::class);

it('accepts a comma separated sku filter', function () {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format' => 'Csv',
            'sku'         => 'SKU001, SKU002, SKU003',
        ],
    ]);
})->throwsNoExceptions();

it('accepts every allowed completeness value', function (string $completeness) {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format'  => 'Csv',
            'completeness' => $completeness,
        ],
    ]);
})->with(['none', 'at_least_one', 'all'])->throwsNoExceptions();

it('rejects an unknown completeness value', function () {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format'  => 'Csv',
            'completeness' => 'not_all',
        ],
    ]);
})->throws(ValidationException::class);

it('accepts every allowed time condition value', function (string $condition) {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format'    => 'Csv',
            'time_condition' => $condition,
            'time_value'     => 7,
            'time_date'      => '2026-01-15',
            'time_date_end'  => '2026-02-20',
        ],
    ]);
})->with(['none', 'last_n_days', 'since_last_export', 'between_dates'])->throwsNoExceptions();

it('rejects an unknown time condition value', function () {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format'    => 'Csv',
            'time_condition' => 'since_date',
        ],
    ]);
})->throws(ValidationException::class);

it('requires the number of days for the last N days condition', function () {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format'    => 'Csv',
            'time_condition' => 'last_n_days',
        ],
    ]);
})->throws(ValidationException::class);

it('rejects a non positive number of days', function () {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format'    => 'Csv',
            'time_condition' => 'last_n_days',
            'time_value'     => 0,
        ],
    ]);
})->throws(ValidationException::class);

it('accepts a valid between dates range', function () {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format'    => 'Csv',
            'time_condition' => 'between_dates',
            'time_date'      => '2026-01-15',
            'time_date_end'  => '2026-02-20',
        ],
    ]);
})->throwsNoExceptions();

it('requires both dates for the between dates condition', function () {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format'    => 'Csv',
            'time_condition' => 'between_dates',
            'time_date'      => '2026-01-15',
        ],
    ]);
})->throws(ValidationException::class);

it('rejects an end date earlier than the start date', function () {
    app(ProductJobValidator::class)->validate([
        'filters' => [
            'file_format'    => 'Csv',
            'time_condition' => 'between_dates',
            'time_date'      => '2026-02-20',
            'time_date_end'  => '2026-01-15',
        ],
    ]);
})->throws(ValidationException::class);

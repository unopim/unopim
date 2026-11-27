<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\Currency;
use Webkul\Core\RequestMemo;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    app()->setLocale('en_US');
    currencySymbolTestResetCore();
});

/**
 * Drop the core singleton and the memoised currency symbols so the next call reads the database.
 */
function currencySymbolTestResetCore(): void
{
    app()->forgetInstance('core');
    app(RequestMemo::class)->forget('core.currency_symbols');
}

/**
 * Store the given symbol on the currency row with the given code and return that currency.
 */
function currencySymbolTestCurrency(string $code, string $symbol): Currency
{
    $currency = Currency::firstOrCreate(['code' => $code], ['symbol' => $symbol]);

    $currency->update(['symbol' => $symbol]);

    currencySymbolTestResetCore();

    return $currency->fresh();
}

it('returns the stored symbol for a code the locale only knows by its ISO code', function (): void {
    currencySymbolTestCurrency('UAH', '₴');

    expect(core()->currencySymbol('UAH'))->toBe('₴');
});

it('returns the stored symbol for a currency model', function (): void {
    $currency = currencySymbolTestCurrency('UAH', '₴');

    expect(core()->currencySymbol($currency))->toBe('₴');
});

it('falls back to the locale symbol when no symbol is stored', function (): void {
    $currency = currencySymbolTestCurrency('EUR', '');

    expect(core()->currencySymbol('EUR'))->toBe('€')
        ->and(core()->currencySymbol($currency))->toBe('€');
});

it('reads the stored symbols once per request', function (): void {
    currencySymbolTestCurrency('UAH', '₴');

    core()->currencySymbol('UAH');

    DB::enableQueryLog();

    core()->currencySymbol('UAH');
    core()->currencySymbol('EUR');

    expect(DB::getQueryLog())->toBeEmpty();
});

it('falls back to the locale symbol for a code that has no currency row', function (): void {
    Currency::where('code', 'JPY')->delete();

    currencySymbolTestResetCore();

    expect(core()->currencySymbol('JPY'))->toBe('¥');
});

it('keeps applying a stored symbol the locale does not use when formatting a price', function (): void {
    currencySymbolTestCurrency('UAH', '₴');

    expect(core()->formatPrice(10, 'UAH'))->toContain('₴')
        ->not->toContain('UAH');
});

it('keeps applying a stored base currency symbol the locale does not use', function (): void {
    currencySymbolTestCurrency('UAH', '₴');

    config()->set('app.currency', 'UAH');

    currencySymbolTestResetCore();

    expect(core()->formatBasePrice(10))->toContain('₴')
        ->not->toContain('UAH');
});

it('formats a price with the locale pattern when the stored symbol matches the locale symbol', function (): void {
    currencySymbolTestCurrency('EUR', '€');

    expect(core()->formatPrice(10, 'EUR'))->toBe((new NumberFormatter('en_US', NumberFormatter::CURRENCY))->formatCurrency(10, 'EUR'));
});

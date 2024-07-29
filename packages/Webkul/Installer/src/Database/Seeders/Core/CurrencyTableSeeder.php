<?php

namespace Webkul\Installer\Database\Seeders\Core;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencyTableSeeder extends Seeder
{
    /**
     * Currency symbols.
     *
     * @var array
     */
    protected $currenciesWithSymbols = [
        'ADP' => 'ADP',
        'AED' => 'د.إ',
        'AFA' => 'AFA',
        'AFN' => '؋',
        'ALK' => 'ALK',
        'ALL' => 'ALL',
        'AMD' => 'AMD',
        'ANG' => 'ANG',
        'AOA' => 'AOA',
        'AOK' => 'AOK',
        'AON' => 'AON',
        'AOR' => 'AOR',
        'ARA' => 'ARA',
        'ARL' => 'ARL',
        'ARM' => 'ARM',
        'ARP' => 'ARP',
        'ARS' => 'ARS',
        'ATS' => 'ATS',
        'AUD' => 'AUD',
        'AWG' => 'AWG',
        'AZM' => 'AZM',
        'AZN' => 'AZN',
        'BAD' => 'BAD',
        'BAM' => 'BAM',
        'BAN' => 'BAN',
        'BBD' => 'BBD',
        'BDT' => 'BDT',
        'BEC' => 'BEC',
        'BEF' => 'BEF',
        'BEL' => 'BEL',
        'BGL' => 'BGL',
        'BGM' => 'BGM',
        'BGN' => 'BGN',
        'BGO' => 'BGO',
        'BHD' => 'BHD',
        'BIF' => 'BIF',
        'BMD' => 'BMD',
        'BND' => 'BND',
        'BOB' => 'BOB',
        'BOL' => 'BOL',
        'BOP' => 'BOP',
        'BOV' => 'BOV',
        'BRB' => 'BRB',
        'BRC' => 'BRC',
        'BRE' => 'BRE',
        'BRL' => 'BRL',
        'BRN' => 'BRN',
        'BRR' => 'BRR',
        'BRZ' => 'BRZ',
        'BSD' => 'BSD',
        'BTN' => 'BTN',
        'BUK' => 'BUK',
        'BWP' => 'BWP',
        'BYB' => 'BYB',
        'BYR' => 'BYR',
        'BZD' => 'BZD',
        'CAD' => 'CAD',
        'CDF' => 'CDF',
        'CHE' => 'CHE',
        'CHF' => 'CHF',
        'CHW' => 'CHW',
        'CLE' => 'CLE',
        'CLF' => 'CLF',
        'CLP' => 'CLP',
        'CNX' => 'CNX',
        'CNY' => '¥',
        'COP' => 'COP',
        'COU' => 'COU',
        'CRC' => 'CRC',
        'CSD' => 'CSD',
        'CSK' => 'CSK',
        'CUC' => 'CUC',
        'CUP' => 'CUP',
        'CVE' => 'CVE',
        'CYP' => 'CYP',
        'CZK' => 'CZK',
        'DDM' => 'DDM',
        'DEM' => 'DEM',
        'DJF' => 'DJF',
        'DKK' => 'DKK',
        'EUR' => '€',
        'GBP' => '£',
        'RUB' => '₽',
        'IRR' => '﷼',
        'INR' => '₹',
        'JPY' => '¥',
        'SAR' => '﷼',
        'TRY' => '₺',
        'UAH' => '₴',
        'USD' => '$',
    ];

    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        DB::table('channels')->delete();

        DB::table('currencies')->delete();

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        $defaultCurrency = $parameters['default_currency'] ?? config('app.currency');

        $enableCurrencies = $parameters['allowed_currencies'] ?? [$defaultCurrency];

        foreach ($this->currenciesWithSymbols as $currency => $currencySymbol) {
            DB::table('currencies')->insert([
                [
                    'id'     => $currency,
                    'code'   => $currency,
                    'symbol' => $currencySymbol,
                    'status' => in_array($currency, $enableCurrencies) ? true : false,
                ],
            ]);
        }
    }
}

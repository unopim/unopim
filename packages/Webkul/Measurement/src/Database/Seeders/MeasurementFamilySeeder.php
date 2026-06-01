<?php

namespace Webkul\Measurement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeasurementFamilySeeder extends Seeder
{
    public function run($parameters = [])
    {
        $parameters = $parameters ?? [];
        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');
        $locales = $parameters['allowed_locales'] ?? [$defaultLocale];

        $makeLabels = function ($value) use ($locales) {
            $labels = [];
            foreach ($locales as $locale) {
                $labels[$locale] = ucfirst($value);
            }

            return $labels;
        };

        $families = [

            /*
            |------------------------------------------------------------------
            | 1. AREA (standard: Square meter)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Area',
                'name'          => 'Area',
                'labels'        => $makeLabels('area'),
                'standard_unit' => 'SQUARE_METER',
                'symbol'        => 'm²',
                'units'         => [
                    ['code' => 'SQUARE_MILLIMETER', 'labels' => $makeLabels('square millimeter'), 'symbol' => 'mm²',    'convert_from_standard' => [['value' => '1000000',      'operator' => 'mul']]],
                    ['code' => 'SQUARE_CENTIMETER', 'labels' => $makeLabels('square centimeter'), 'symbol' => 'cm²',    'convert_from_standard' => [['value' => '10000',        'operator' => 'mul']]],
                    ['code' => 'SQUARE_DECIMETER',  'labels' => $makeLabels('square decimeter'),  'symbol' => 'dm²',    'convert_from_standard' => [['value' => '100',          'operator' => 'mul']]],
                    ['code' => 'SQUARE_METER',      'labels' => $makeLabels('square meter'),      'symbol' => 'm²',     'convert_from_standard' => [['value' => '1',            'operator' => 'mul']]],
                    ['code' => 'CENTIARE',          'labels' => $makeLabels('centiare'),          'symbol' => 'ca',     'convert_from_standard' => [['value' => '1',            'operator' => 'mul']]],
                    ['code' => 'SQUARE_DEKAMETER',  'labels' => $makeLabels('square dekameter'),  'symbol' => 'dam²',   'convert_from_standard' => [['value' => '100',          'operator' => 'div']]],
                    ['code' => 'ARE',               'labels' => $makeLabels('are'),               'symbol' => 'a',      'convert_from_standard' => [['value' => '100',          'operator' => 'div']]],
                    ['code' => 'SQUARE_HECTOMETER', 'labels' => $makeLabels('square hectometer'), 'symbol' => 'hm²',    'convert_from_standard' => [['value' => '10000',        'operator' => 'div']]],
                    ['code' => 'HECTARE',           'labels' => $makeLabels('hectare'),           'symbol' => 'ha',     'convert_from_standard' => [['value' => '10000',        'operator' => 'div']]],
                    ['code' => 'SQUARE_KILOMETER',  'labels' => $makeLabels('square kilometer'),  'symbol' => 'km²',    'convert_from_standard' => [['value' => '1000000',      'operator' => 'div']]],
                    ['code' => 'SQUARE_MIL',        'labels' => $makeLabels('square mil'),        'symbol' => 'sq mil', 'convert_from_standard' => [['value' => '1550003100',   'operator' => 'mul']]],
                    ['code' => 'SQUARE_INCH',       'labels' => $makeLabels('square inch'),       'symbol' => 'in²',    'convert_from_standard' => [['value' => '1550.0031',    'operator' => 'mul']]],
                    ['code' => 'SQUARE_FOOT',       'labels' => $makeLabels('square foot'),       'symbol' => 'ft²',    'convert_from_standard' => [['value' => '10.7639',      'operator' => 'mul']]],
                    ['code' => 'SQUARE_YARD',       'labels' => $makeLabels('square yard'),       'symbol' => 'yd²',    'convert_from_standard' => [['value' => '1.19599',      'operator' => 'mul']]],
                    ['code' => 'ACRE',              'labels' => $makeLabels('acre'),              'symbol' => 'A',      'convert_from_standard' => [['value' => '0.000247105',  'operator' => 'mul']]],
                    ['code' => 'SQUARE_FURLONG',    'labels' => $makeLabels('square furlong'),    'symbol' => 'fur²',   'convert_from_standard' => [['value' => '0.0000024711', 'operator' => 'mul']]],
                    ['code' => 'SQUARE_MILE',       'labels' => $makeLabels('square mile'),       'symbol' => 'mi²',    'convert_from_standard' => [['value' => '3.861e-7',     'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 2. BINARY (standard: Byte)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Binary',
                'name'          => 'Binary',
                'labels'        => $makeLabels('binary'),
                'standard_unit' => 'BYTE',
                'symbol'        => 'B',
                'units'         => [
                    ['code' => 'BIT',      'labels' => $makeLabels('bit'),      'symbol' => 'b',  'convert_from_standard' => [['value' => '8',             'operator' => 'mul']]],
                    ['code' => 'BYTE',     'labels' => $makeLabels('byte'),     'symbol' => 'B',  'convert_from_standard' => [['value' => '1',             'operator' => 'mul']]],
                    ['code' => 'KILOBYTE', 'labels' => $makeLabels('kilobyte'), 'symbol' => 'kB', 'convert_from_standard' => [['value' => '1024',          'operator' => 'div']]],
                    ['code' => 'MEGABYTE', 'labels' => $makeLabels('megabyte'), 'symbol' => 'MB', 'convert_from_standard' => [['value' => '1048576',       'operator' => 'div']]],
                    ['code' => 'GIGABYTE', 'labels' => $makeLabels('gigabyte'), 'symbol' => 'GB', 'convert_from_standard' => [['value' => '1073741824',    'operator' => 'div']]],
                    ['code' => 'TERABYTE', 'labels' => $makeLabels('terabyte'), 'symbol' => 'TB', 'convert_from_standard' => [['value' => '1099511627776', 'operator' => 'div']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 3. BRIGHTNESS / Luminous flux (standard: Lumen)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Brightness',
                'name'          => 'Brightness',
                'labels'        => $makeLabels('brightness'),
                'standard_unit' => 'LUMEN',
                'symbol'        => 'lm',
                'units'         => [
                    ['code' => 'LUMEN',     'labels' => $makeLabels('lumen'),     'symbol' => 'lm',  'convert_from_standard' => [['value' => '1',    'operator' => 'mul']]],
                    ['code' => 'MILLILUMEN', 'labels' => $makeLabels('millilumen'), 'symbol' => 'mlm', 'convert_from_standard' => [['value' => '1000', 'operator' => 'mul']]],
                    ['code' => 'KILOLUMEN', 'labels' => $makeLabels('kilolumen'),  'symbol' => 'klm', 'convert_from_standard' => [['value' => '1000', 'operator' => 'div']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 4. CAPACITANCE (standard: Farad)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Capacitance',
                'name'          => 'Capacitance',
                'labels'        => $makeLabels('capacitance'),
                'standard_unit' => 'FARAD',
                'symbol'        => 'F',
                'units'         => [
                    ['code' => 'PICOFARAD',  'labels' => $makeLabels('picofarad'),  'symbol' => 'pF', 'convert_from_standard' => [['value' => '1000000000000', 'operator' => 'mul']]],
                    ['code' => 'NANOFARAD',  'labels' => $makeLabels('nanofarad'),  'symbol' => 'nF', 'convert_from_standard' => [['value' => '1000000000',    'operator' => 'mul']]],
                    ['code' => 'MICROFARAD', 'labels' => $makeLabels('microfarad'), 'symbol' => 'µF', 'convert_from_standard' => [['value' => '1000000',       'operator' => 'mul']]],
                    ['code' => 'MILLIFARAD', 'labels' => $makeLabels('millifarad'), 'symbol' => 'mF', 'convert_from_standard' => [['value' => '1000',          'operator' => 'mul']]],
                    ['code' => 'FARAD',      'labels' => $makeLabels('farad'),      'symbol' => 'F',  'convert_from_standard' => [['value' => '1',             'operator' => 'mul']]],
                    ['code' => 'KILOFARAD',  'labels' => $makeLabels('kilofarad'),  'symbol' => 'kF', 'convert_from_standard' => [['value' => '1000',          'operator' => 'div']]],
                    ['code' => 'MEGAFARAD',  'labels' => $makeLabels('megafarad'),  'symbol' => 'MF', 'convert_from_standard' => [['value' => '1000000',       'operator' => 'div']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 5. DECIBEL (standard: Decibel)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Decibel',
                'name'          => 'Decibel',
                'labels'        => $makeLabels('decibel'),
                'standard_unit' => 'DECIBEL',
                'symbol'        => 'dB',
                'units'         => [
                    ['code' => 'DECIBEL', 'labels' => $makeLabels('decibel'), 'symbol' => 'dB', 'convert_from_standard' => [['value' => '1', 'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 6. DURATION (standard: Second)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Duration',
                'name'          => 'Duration',
                'labels'        => $makeLabels('duration'),
                'standard_unit' => 'SECOND',
                'symbol'        => 's',
                'units'         => [
                    ['code' => 'MILLISECOND', 'labels' => $makeLabels('millisecond'), 'symbol' => 'ms',  'convert_from_standard' => [['value' => '1000',      'operator' => 'mul']]],
                    ['code' => 'SECOND',      'labels' => $makeLabels('second'),      'symbol' => 's',   'convert_from_standard' => [['value' => '1',         'operator' => 'mul']]],
                    ['code' => 'MINUTE',      'labels' => $makeLabels('minute'),      'symbol' => 'min', 'convert_from_standard' => [['value' => '60',        'operator' => 'div']]],
                    ['code' => 'HOUR',        'labels' => $makeLabels('hour'),        'symbol' => 'h',   'convert_from_standard' => [['value' => '3600',      'operator' => 'div']]],
                    ['code' => 'DAY',         'labels' => $makeLabels('day'),         'symbol' => 'd',   'convert_from_standard' => [['value' => '86400',     'operator' => 'div']]],
                    ['code' => 'WEEK',        'labels' => $makeLabels('week'),        'symbol' => 'wk',  'convert_from_standard' => [['value' => '604800',    'operator' => 'div']]],
                    ['code' => 'MONTH',       'labels' => $makeLabels('month'),       'symbol' => 'mo',  'convert_from_standard' => [['value' => '2628000',   'operator' => 'div']]],
                    ['code' => 'YEAR',        'labels' => $makeLabels('year'),        'symbol' => 'yr',  'convert_from_standard' => [['value' => '31536000',  'operator' => 'div']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 7. ELECTRIC CHARGE (standard: Ampere hour)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'ElectricCharge',
                'name'          => 'Electric Charge',
                'labels'        => $makeLabels('electric charge'),
                'standard_unit' => 'AMPEREHOUR',
                'symbol'        => 'Ah',
                'units'         => [
                    ['code' => 'MILLIAMPEREHOUR', 'labels' => $makeLabels('milliamperehour'), 'symbol' => 'mAh', 'convert_from_standard' => [['value' => '1000',   'operator' => 'mul']]],
                    ['code' => 'AMPEREHOUR',      'labels' => $makeLabels('amperehour'),      'symbol' => 'Ah',  'convert_from_standard' => [['value' => '1',      'operator' => 'mul']]],
                    ['code' => 'MILLICOULOMB',    'labels' => $makeLabels('millicoulomb'),    'symbol' => 'mC',  'convert_from_standard' => [['value' => '3600000', 'operator' => 'mul']]],
                    ['code' => 'CENTICOULOMB',    'labels' => $makeLabels('centicoulomb'),    'symbol' => 'cC',  'convert_from_standard' => [['value' => '360000',  'operator' => 'mul']]],
                    ['code' => 'DECICOULOMB',     'labels' => $makeLabels('decicoulomb'),     'symbol' => 'dC',  'convert_from_standard' => [['value' => '36000',   'operator' => 'mul']]],
                    ['code' => 'COULOMB',         'labels' => $makeLabels('coulomb'),         'symbol' => 'C',   'convert_from_standard' => [['value' => '3600',    'operator' => 'mul']]],
                    ['code' => 'DEKACOULOMB',     'labels' => $makeLabels('dekacoulomb'),     'symbol' => 'daC', 'convert_from_standard' => [['value' => '360',     'operator' => 'mul']]],
                    ['code' => 'HECTOCOULOMB',    'labels' => $makeLabels('hectocoulomb'),    'symbol' => 'hC',  'convert_from_standard' => [['value' => '36',      'operator' => 'mul']]],
                    ['code' => 'KILOCOULOMB',     'labels' => $makeLabels('kilocoulomb'),     'symbol' => 'kC',  'convert_from_standard' => [['value' => '3.6',     'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 8. ENERGY (standard: Joule)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Energy',
                'name'          => 'Energy',
                'labels'        => $makeLabels('energy'),
                'standard_unit' => 'JOULE',
                'symbol'        => 'J',
                'units'         => [
                    ['code' => 'JOULE',      'labels' => $makeLabels('joule'),      'symbol' => 'J',    'convert_from_standard' => [['value' => '1',       'operator' => 'mul']]],
                    ['code' => 'KILOJOULE',  'labels' => $makeLabels('kilojoule'),  'symbol' => 'kJ',   'convert_from_standard' => [['value' => '1000',    'operator' => 'div']]],
                    ['code' => 'MEGAJOULE',  'labels' => $makeLabels('megajoule'),  'symbol' => 'MJ',   'convert_from_standard' => [['value' => '1000000', 'operator' => 'div']]],
                    ['code' => 'CALORIE',    'labels' => $makeLabels('calorie'),    'symbol' => 'cal',  'convert_from_standard' => [['value' => '4.184',   'operator' => 'div']]],
                    ['code' => 'KILOCALORIE', 'labels' => $makeLabels('kilocalorie'), 'symbol' => 'kcal', 'convert_from_standard' => [['value' => '4184',    'operator' => 'div']]],
                    ['code' => 'WATTHOUR',   'labels' => $makeLabels('watt hour'),  'symbol' => 'Wh',   'convert_from_standard' => [['value' => '3600',    'operator' => 'div']]],
                    ['code' => 'KILOWATTHOUR', 'labels'=> $makeLabels('kilowatt hour'), 'symbol'=> 'kWh', 'convert_from_standard' => [['value' => '3600000', 'operator' => 'div']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 9. FORCE (standard: Newton)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Force',
                'name'          => 'Force',
                'labels'        => $makeLabels('force'),
                'standard_unit' => 'NEWTON',
                'symbol'        => 'N',
                'units'         => [
                    ['code' => 'MILLINEWTON',     'labels' => $makeLabels('millinewton'),     'symbol' => 'mN',  'convert_from_standard' => [['value' => '1000',    'operator' => 'mul']]],
                    ['code' => 'NEWTON',          'labels' => $makeLabels('newton'),          'symbol' => 'N',   'convert_from_standard' => [['value' => '1',       'operator' => 'mul']]],
                    ['code' => 'KILONEWTON',      'labels' => $makeLabels('kilonewton'),      'symbol' => 'kN',  'convert_from_standard' => [['value' => '1000',    'operator' => 'div']]],
                    ['code' => 'MEGANEWTON',      'labels' => $makeLabels('meganewton'),      'symbol' => 'MN',  'convert_from_standard' => [['value' => '1000000', 'operator' => 'div']]],
                    ['code' => 'OUNCE_FORCE',     'labels' => $makeLabels('ounce force'),     'symbol' => 'ozf', 'convert_from_standard' => [['value' => '3.59694', 'operator' => 'mul']]],
                    ['code' => 'POUND_FORCE',     'labels' => $makeLabels('pound force'),     'symbol' => 'lbf', 'convert_from_standard' => [['value' => '0.224809', 'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 10. FREQUENCY (standard: Hertz)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Frequency',
                'name'          => 'Frequency',
                'labels'        => $makeLabels('frequency'),
                'standard_unit' => 'HERTZ',
                'symbol'        => 'Hz',
                'units'         => [
                    ['code' => 'HERTZ',     'labels' => $makeLabels('hertz'),     'symbol' => 'Hz',  'convert_from_standard' => [['value' => '1',          'operator' => 'mul']]],
                    ['code' => 'KILOHERTZ', 'labels' => $makeLabels('kilohertz'), 'symbol' => 'kHz', 'convert_from_standard' => [['value' => '1000',       'operator' => 'div']]],
                    ['code' => 'MEGAHERTZ', 'labels' => $makeLabels('megahertz'), 'symbol' => 'MHz', 'convert_from_standard' => [['value' => '1000000',    'operator' => 'div']]],
                    ['code' => 'GIGAHERTZ', 'labels' => $makeLabels('gigahertz'), 'symbol' => 'GHz', 'convert_from_standard' => [['value' => '1000000000', 'operator' => 'div']]],
                    ['code' => 'TERAHERTZ', 'labels' => $makeLabels('terahertz'), 'symbol' => 'THz', 'convert_from_standard' => [['value' => '1000000000000', 'operator' => 'div']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 11. INTENSITY / Electric current (standard: Ampere)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Intensity',
                'name'          => 'Intensity',
                'labels'        => $makeLabels('intensity'),
                'standard_unit' => 'AMPERE',
                'symbol'        => 'A',
                'units'         => [
                    ['code' => 'MILLIAMPERE',  'labels' => $makeLabels('milliampere'),  'symbol' => 'mA',  'convert_from_standard' => [['value' => '1000',  'operator' => 'mul']]],
                    ['code' => 'CENTIAMPERE',  'labels' => $makeLabels('centiampere'),  'symbol' => 'cA',  'convert_from_standard' => [['value' => '100',   'operator' => 'mul']]],
                    ['code' => 'DECIAMPERE',   'labels' => $makeLabels('deciampere'),   'symbol' => 'dA',  'convert_from_standard' => [['value' => '10',    'operator' => 'mul']]],
                    ['code' => 'AMPERE',       'labels' => $makeLabels('ampere'),       'symbol' => 'A',   'convert_from_standard' => [['value' => '1',     'operator' => 'mul']]],
                    ['code' => 'DEKAMPERE',    'labels' => $makeLabels('dekampere'),    'symbol' => 'daA', 'convert_from_standard' => [['value' => '10',    'operator' => 'div']]],
                    ['code' => 'HECTOAMPERE',  'labels' => $makeLabels('hectoampere'),  'symbol' => 'hA',  'convert_from_standard' => [['value' => '100',   'operator' => 'div']]],
                    ['code' => 'KILOAMPERE',   'labels' => $makeLabels('kiloampere'),   'symbol' => 'kA',  'convert_from_standard' => [['value' => '1000',  'operator' => 'div']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 12. LENGTH (standard: Meter)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Length',
                'name'          => 'Length',
                'labels'        => $makeLabels('length'),
                'standard_unit' => 'METER',
                'symbol'        => 'm',
                'units'         => [
                    ['code' => 'MILLIMETER',  'labels' => $makeLabels('millimeter'),  'symbol' => 'mm',  'convert_from_standard' => [['value' => '1000',        'operator' => 'mul']]],
                    ['code' => 'CENTIMETER',  'labels' => $makeLabels('centimeter'),  'symbol' => 'cm',  'convert_from_standard' => [['value' => '100',         'operator' => 'mul']]],
                    ['code' => 'DECIMETER',   'labels' => $makeLabels('decimeter'),   'symbol' => 'dm',  'convert_from_standard' => [['value' => '10',          'operator' => 'mul']]],
                    ['code' => 'METER',       'labels' => $makeLabels('meter'),       'symbol' => 'm',   'convert_from_standard' => [['value' => '1',           'operator' => 'mul']]],
                    ['code' => 'DEKAMETER',   'labels' => $makeLabels('dekameter'),   'symbol' => 'dam', 'convert_from_standard' => [['value' => '10',          'operator' => 'div']]],
                    ['code' => 'HECTOMETER',  'labels' => $makeLabels('hectometer'),  'symbol' => 'hm',  'convert_from_standard' => [['value' => '100',         'operator' => 'div']]],
                    ['code' => 'KILOMETER',   'labels' => $makeLabels('kilometer'),   'symbol' => 'km',  'convert_from_standard' => [['value' => '1000',        'operator' => 'div']]],
                    ['code' => 'MIL',         'labels' => $makeLabels('mil'),         'symbol' => 'mil', 'convert_from_standard' => [['value' => '39370.1',     'operator' => 'mul']]],
                    ['code' => 'INCH',        'labels' => $makeLabels('inch'),        'symbol' => 'in',  'convert_from_standard' => [['value' => '39.3701',     'operator' => 'mul']]],
                    ['code' => 'FEET',        'labels' => $makeLabels('feet'),        'symbol' => 'ft',  'convert_from_standard' => [['value' => '3.28084',     'operator' => 'mul']]],
                    ['code' => 'YARD',        'labels' => $makeLabels('yard'),        'symbol' => 'yd',  'convert_from_standard' => [['value' => '1.09361',     'operator' => 'mul']]],
                    ['code' => 'CHAIN',       'labels' => $makeLabels('chain'),       'symbol' => 'ch',  'convert_from_standard' => [['value' => '0.0497097',   'operator' => 'mul']]],
                    ['code' => 'FURLONG',     'labels' => $makeLabels('furlong'),     'symbol' => 'fur', 'convert_from_standard' => [['value' => '0.00497097',  'operator' => 'mul']]],
                    ['code' => 'MILE',        'labels' => $makeLabels('mile'),        'symbol' => 'mi',  'convert_from_standard' => [['value' => '0.000621371', 'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 13. PACKAGING (standard: Piece)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Packaging',
                'name'          => 'Packaging',
                'labels'        => $makeLabels('packaging'),
                'standard_unit' => 'PIECE',
                'symbol'        => 'pc',
                'units'         => [
                    ['code' => 'PIECE',  'labels' => $makeLabels('piece'),  'symbol' => 'pc',  'convert_from_standard' => [['value' => '1',   'operator' => 'mul']]],
                    ['code' => 'DOZEN',  'labels' => $makeLabels('dozen'),  'symbol' => 'dz',  'convert_from_standard' => [['value' => '12',  'operator' => 'div']]],
                    ['code' => 'GROSS',  'labels' => $makeLabels('gross'),  'symbol' => 'gr',  'convert_from_standard' => [['value' => '144', 'operator' => 'div']]],
                    ['code' => 'PAIR',   'labels' => $makeLabels('pair'),   'symbol' => 'pr',  'convert_from_standard' => [['value' => '2',   'operator' => 'div']]],
                    ['code' => 'UNIT',   'labels' => $makeLabels('unit'),   'symbol' => 'u',   'convert_from_standard' => [['value' => '1',   'operator' => 'mul']]],
                    ['code' => 'SET',    'labels' => $makeLabels('set'),    'symbol' => 'set', 'convert_from_standard' => [['value' => '1',   'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 14. POWER (standard: Watt)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Power',
                'name'          => 'Power',
                'labels'        => $makeLabels('power'),
                'standard_unit' => 'WATT',
                'symbol'        => 'W',
                'units'         => [
                    ['code' => 'MILLIWATT', 'labels' => $makeLabels('milliwatt'), 'symbol' => 'mW', 'convert_from_standard' => [['value' => '1000',          'operator' => 'mul']]],
                    ['code' => 'WATT',      'labels' => $makeLabels('watt'),      'symbol' => 'W',  'convert_from_standard' => [['value' => '1',             'operator' => 'mul']]],
                    ['code' => 'KILOWATT',  'labels' => $makeLabels('kilowatt'),  'symbol' => 'kW', 'convert_from_standard' => [['value' => '1000',          'operator' => 'div']]],
                    ['code' => 'MEGAWATT',  'labels' => $makeLabels('megawatt'),  'symbol' => 'MW', 'convert_from_standard' => [['value' => '1000000',       'operator' => 'div']]],
                    ['code' => 'GIGAWATT',  'labels' => $makeLabels('gigawatt'),  'symbol' => 'GW', 'convert_from_standard' => [['value' => '1000000000',    'operator' => 'div']]],
                    ['code' => 'TERAWATT',  'labels' => $makeLabels('terawatt'),  'symbol' => 'TW', 'convert_from_standard' => [['value' => '1000000000000', 'operator' => 'div']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 15. PRESSURE (standard: Bar)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Pressure',
                'name'          => 'Pressure',
                'labels'        => $makeLabels('pressure'),
                'standard_unit' => 'BAR',
                'symbol'        => 'bar',
                'units'         => [
                    ['code' => 'MILLIBAR',   'labels' => $makeLabels('millibar'),   'symbol' => 'mbar', 'convert_from_standard' => [['value' => '1000',    'operator' => 'mul']]],
                    ['code' => 'CENTIBAR',   'labels' => $makeLabels('centibar'),   'symbol' => 'cbar', 'convert_from_standard' => [['value' => '100',     'operator' => 'mul']]],
                    ['code' => 'BAR',        'labels' => $makeLabels('bar'),        'symbol' => 'bar',  'convert_from_standard' => [['value' => '1',       'operator' => 'mul']]],
                    ['code' => 'KILOBAR',    'labels' => $makeLabels('kilobar'),    'symbol' => 'kbar', 'convert_from_standard' => [['value' => '1000',    'operator' => 'div']]],
                    ['code' => 'MEGABAR',    'labels' => $makeLabels('megabar'),    'symbol' => 'Mbar', 'convert_from_standard' => [['value' => '1000000', 'operator' => 'div']]],
                    ['code' => 'PASCAL',     'labels' => $makeLabels('pascal'),     'symbol' => 'Pa',   'convert_from_standard' => [['value' => '100000',  'operator' => 'mul']]],
                    ['code' => 'KILOPASCAL', 'labels' => $makeLabels('kilopascal'), 'symbol' => 'kPa',  'convert_from_standard' => [['value' => '100',     'operator' => 'mul']]],
                    ['code' => 'MEGAPASCAL', 'labels' => $makeLabels('megapascal'), 'symbol' => 'MPa',  'convert_from_standard' => [['value' => '10',      'operator' => 'mul']]],
                    ['code' => 'ATMOSPHERE', 'labels' => $makeLabels('atmosphere'), 'symbol' => 'atm',  'convert_from_standard' => [['value' => '1.01325', 'operator' => 'div']]],
                    ['code' => 'PSI',        'labels' => $makeLabels('psi'),        'symbol' => 'psi',  'convert_from_standard' => [['value' => '14.5038', 'operator' => 'mul']]],
                    ['code' => 'TORR',       'labels' => $makeLabels('torr'),       'symbol' => 'Torr', 'convert_from_standard' => [['value' => '750.062', 'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 16. RESISTANCE (standard: Ohm)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Resistance',
                'name'          => 'Resistance',
                'labels'        => $makeLabels('resistance'),
                'standard_unit' => 'OHM',
                'symbol'        => 'Ω',
                'units'         => [
                    ['code' => 'MILLIOHM', 'labels' => $makeLabels('milliohm'), 'symbol' => 'mΩ', 'convert_from_standard' => [['value' => '1000',    'operator' => 'mul']]],
                    ['code' => 'CENTIOHM', 'labels' => $makeLabels('centiohm'), 'symbol' => 'cΩ', 'convert_from_standard' => [['value' => '100',     'operator' => 'mul']]],
                    ['code' => 'DECIOHM',  'labels' => $makeLabels('deciohm'),  'symbol' => 'dΩ', 'convert_from_standard' => [['value' => '10',      'operator' => 'mul']]],
                    ['code' => 'OHM',      'labels' => $makeLabels('ohm'),      'symbol' => 'Ω',  'convert_from_standard' => [['value' => '1',       'operator' => 'mul']]],
                    ['code' => 'DEKAOHM',  'labels' => $makeLabels('dekaohm'),  'symbol' => 'daΩ', 'convert_from_standard' => [['value' => '10',      'operator' => 'div']]],
                    ['code' => 'HECTOHM',  'labels' => $makeLabels('hectohm'),  'symbol' => 'hΩ', 'convert_from_standard' => [['value' => '100',     'operator' => 'div']]],
                    ['code' => 'KILOHM',   'labels' => $makeLabels('kilohm'),   'symbol' => 'kΩ', 'convert_from_standard' => [['value' => '1000',    'operator' => 'div']]],
                    ['code' => 'MEGAOHM',  'labels' => $makeLabels('megaohm'),  'symbol' => 'MΩ', 'convert_from_standard' => [['value' => '1000000', 'operator' => 'div']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 17. SPEED (standard: Meter per second)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Speed',
                'name'          => 'Speed',
                'labels'        => $makeLabels('speed'),
                'standard_unit' => 'METER_PER_SECOND',
                'symbol'        => 'm/s',
                'units'         => [
                    ['code' => 'METER_PER_SECOND',     'labels' => $makeLabels('meter per second'),     'symbol' => 'm/s',   'convert_from_standard' => [['value' => '1',        'operator' => 'mul']]],
                    ['code' => 'METER_PER_MINUTE',     'labels' => $makeLabels('meter per minute'),     'symbol' => 'm/min', 'convert_from_standard' => [['value' => '60',       'operator' => 'mul']]],
                    ['code' => 'METER_PER_HOUR',       'labels' => $makeLabels('meter per hour'),       'symbol' => 'm/h',   'convert_from_standard' => [['value' => '3600',     'operator' => 'mul']]],
                    ['code' => 'KILOMETER_PER_HOUR',   'labels' => $makeLabels('kilometer per hour'),   'symbol' => 'km/h',  'convert_from_standard' => [['value' => '3.6',      'operator' => 'mul']]],
                    ['code' => 'FOOT_PER_SECOND',      'labels' => $makeLabels('foot per second'),      'symbol' => 'ft/s',  'convert_from_standard' => [['value' => '3.28084',  'operator' => 'mul']]],
                    ['code' => 'FOOT_PER_HOUR',        'labels' => $makeLabels('foot per hour'),        'symbol' => 'ft/h',  'convert_from_standard' => [['value' => '11811.0',  'operator' => 'mul']]],
                    ['code' => 'YARD_PER_HOUR',        'labels' => $makeLabels('yard per hour'),        'symbol' => 'yd/h',  'convert_from_standard' => [['value' => '3937.01',  'operator' => 'mul']]],
                    ['code' => 'MILE_PER_HOUR',        'labels' => $makeLabels('mile per hour'),        'symbol' => 'mph',   'convert_from_standard' => [['value' => '2.23694',  'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 18. TEMPERATURE (standard: Kelvin)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Temperature',
                'name'          => 'Temperature',
                'labels'        => $makeLabels('temperature'),
                'standard_unit' => 'KELVIN',
                'symbol'        => '°K',
                'units'         => [
                    ['code' => 'CELSIUS',    'labels' => $makeLabels('celsius'),    'symbol' => '°C', 'convert_from_standard' => [['value' => '273.15', 'operator' => 'sub']]],
                    ['code' => 'FAHRENHEIT', 'labels' => $makeLabels('fahrenheit'), 'symbol' => '°F', 'convert_from_standard' => [['value' => '273.15', 'operator' => 'sub'], ['value' => '1.8', 'operator' => 'mul'], ['value' => '32', 'operator' => 'add']]],
                    ['code' => 'KELVIN',     'labels' => $makeLabels('kelvin'),     'symbol' => '°K', 'convert_from_standard' => [['value' => '1',      'operator' => 'mul']]],
                    ['code' => 'RANKINE',    'labels' => $makeLabels('rankine'),    'symbol' => '°R', 'convert_from_standard' => [['value' => '1.8',    'operator' => 'mul']]],
                    ['code' => 'REAUMUR',    'labels' => $makeLabels('reaumur'),    'symbol' => '°r', 'convert_from_standard' => [['value' => '273.15', 'operator' => 'sub'], ['value' => '0.8', 'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 19. VOLTAGE (standard: Volt)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Voltage',
                'name'          => 'Voltage',
                'labels'        => $makeLabels('voltage'),
                'standard_unit' => 'VOLT',
                'symbol'        => 'V',
                'units'         => [
                    ['code' => 'MILLIVOLT',  'labels' => $makeLabels('millivolt'),  'symbol' => 'mV',  'convert_from_standard' => [['value' => '1000',    'operator' => 'mul']]],
                    ['code' => 'CENTIVOLT',  'labels' => $makeLabels('centivolt'),  'symbol' => 'cV',  'convert_from_standard' => [['value' => '100',     'operator' => 'mul']]],
                    ['code' => 'DECIVOLT',   'labels' => $makeLabels('decivolt'),   'symbol' => 'dV',  'convert_from_standard' => [['value' => '10',      'operator' => 'mul']]],
                    ['code' => 'VOLT',       'labels' => $makeLabels('volt'),       'symbol' => 'V',   'convert_from_standard' => [['value' => '1',       'operator' => 'mul']]],
                    ['code' => 'DEKAVOLT',   'labels' => $makeLabels('dekavolt'),   'symbol' => 'daV', 'convert_from_standard' => [['value' => '10',      'operator' => 'div']]],
                    ['code' => 'HECTOVOLT',  'labels' => $makeLabels('hectovolt'),  'symbol' => 'hV',  'convert_from_standard' => [['value' => '100',     'operator' => 'div']]],
                    ['code' => 'KILOVOLT',   'labels' => $makeLabels('kilovolt'),   'symbol' => 'kV',  'convert_from_standard' => [['value' => '1000',    'operator' => 'div']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 20. VOLUME (standard: Cubic meter)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Volume',
                'name'          => 'Volume',
                'labels'        => $makeLabels('volume'),
                'standard_unit' => 'CUBIC_METER',
                'symbol'        => 'm³',
                'units'         => [
                    ['code' => 'CUBIC_MILLIMETER', 'labels' => $makeLabels('cubic millimeter'), 'symbol' => 'mm³',  'convert_from_standard' => [['value' => '1000000000', 'operator' => 'mul']]],
                    ['code' => 'CUBIC_CENTIMETER', 'labels' => $makeLabels('cubic centimeter'), 'symbol' => 'cm³',  'convert_from_standard' => [['value' => '1000000',    'operator' => 'mul']]],
                    ['code' => 'MILLILITER',       'labels' => $makeLabels('milliliter'),       'symbol' => 'ml',   'convert_from_standard' => [['value' => '1000000',    'operator' => 'mul']]],
                    ['code' => 'CENTILITER',       'labels' => $makeLabels('centiliter'),       'symbol' => 'cl',   'convert_from_standard' => [['value' => '100000',     'operator' => 'mul']]],
                    ['code' => 'DECILITER',        'labels' => $makeLabels('deciliter'),        'symbol' => 'dl',   'convert_from_standard' => [['value' => '10000',      'operator' => 'mul']]],
                    ['code' => 'CUBIC_DECIMETER',  'labels' => $makeLabels('cubic decimeter'),  'symbol' => 'dm³',  'convert_from_standard' => [['value' => '1000',       'operator' => 'mul']]],
                    ['code' => 'LITER',            'labels' => $makeLabels('liter'),            'symbol' => 'l',    'convert_from_standard' => [['value' => '1000',       'operator' => 'mul']]],
                    ['code' => 'CUBIC_METER',      'labels' => $makeLabels('cubic meter'),      'symbol' => 'm³',   'convert_from_standard' => [['value' => '1',          'operator' => 'mul']]],
                    ['code' => 'PINT',             'labels' => $makeLabels('pint'),             'symbol' => 'pt',   'convert_from_standard' => [['value' => '2113.38',    'operator' => 'mul']]],
                    ['code' => 'BARREL',           'labels' => $makeLabels('barrel'),           'symbol' => 'bbl',  'convert_from_standard' => [['value' => '6.28981',    'operator' => 'mul']]],
                    ['code' => 'GALLON',           'labels' => $makeLabels('gallon'),           'symbol' => 'gal',  'convert_from_standard' => [['value' => '264.172',    'operator' => 'mul']]],
                    ['code' => 'CUBIC_FOOT',       'labels' => $makeLabels('cubic foot'),       'symbol' => 'ft³',  'convert_from_standard' => [['value' => '35.3147',    'operator' => 'mul']]],
                    ['code' => 'CUBIC_INCH',       'labels' => $makeLabels('cubic inch'),       'symbol' => 'in³',  'convert_from_standard' => [['value' => '61023.7',    'operator' => 'mul']]],
                    ['code' => 'CUBIC_YARD',       'labels' => $makeLabels('cubic yard'),       'symbol' => 'yd³',  'convert_from_standard' => [['value' => '1.30795',    'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 21. VOLUME FLOW (standard: Cubic meter per second)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'VolumeFlow',
                'name'          => 'Volume Flow',
                'labels'        => $makeLabels('volume flow'),
                'standard_unit' => 'CUBIC_METER_PER_SECOND',
                'symbol'        => 'm³/s',
                'units'         => [
                    ['code' => 'CUBIC_METER_PER_SECOND', 'labels' => $makeLabels('cubic meter per second'), 'symbol' => 'm³/s',   'convert_from_standard' => [['value' => '1',        'operator' => 'mul']]],
                    ['code' => 'LITER_PER_SECOND',       'labels' => $makeLabels('liter per second'),       'symbol' => 'l/s',    'convert_from_standard' => [['value' => '1000',     'operator' => 'mul']]],
                    ['code' => 'LITER_PER_MINUTE',       'labels' => $makeLabels('liter per minute'),       'symbol' => 'l/min',  'convert_from_standard' => [['value' => '60000',    'operator' => 'mul']]],
                    ['code' => 'LITER_PER_HOUR',         'labels' => $makeLabels('liter per hour'),         'symbol' => 'l/h',    'convert_from_standard' => [['value' => '3600000',  'operator' => 'mul']]],
                    ['code' => 'CUBIC_FOOT_PER_MINUTE',  'labels' => $makeLabels('cubic foot per minute'),  'symbol' => 'ft³/min', 'convert_from_standard' => [['value' => '2118.88',  'operator' => 'mul']]],
                    ['code' => 'GALLON_PER_MINUTE',      'labels' => $makeLabels('gallon per minute'),      'symbol' => 'gal/min', 'convert_from_standard' => [['value' => '15850.4',  'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 22. WEIGHT (standard: Kilogram)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Weight',
                'name'          => 'Weight',
                'labels'        => $makeLabels('weight'),
                'standard_unit' => 'KILOGRAM',
                'symbol'        => 'kg',
                'units'         => [
                    ['code' => 'MILLIGRAM',  'labels' => $makeLabels('milligram'),  'symbol' => 'mg',     'convert_from_standard' => [['value' => '1000000',   'operator' => 'mul']]],
                    ['code' => 'GRAM',       'labels' => $makeLabels('gram'),       'symbol' => 'g',      'convert_from_standard' => [['value' => '1000',      'operator' => 'mul']]],
                    ['code' => 'KILOGRAM',   'labels' => $makeLabels('kilogram'),   'symbol' => 'kg',     'convert_from_standard' => [['value' => '1',         'operator' => 'mul']]],
                    ['code' => 'TON',        'labels' => $makeLabels('ton'),        'symbol' => 't',      'convert_from_standard' => [['value' => '1000',      'operator' => 'div']]],
                    ['code' => 'GRAIN',      'labels' => $makeLabels('grain'),      'symbol' => 'gr',     'convert_from_standard' => [['value' => '15432.4',   'operator' => 'mul']]],
                    ['code' => 'DENIER',     'labels' => $makeLabels('denier'),     'symbol' => 'denier', 'convert_from_standard' => [['value' => '9000000',   'operator' => 'mul']]],
                    ['code' => 'ONCE',       'labels' => $makeLabels('once'),       'symbol' => 'once',   'convert_from_standard' => [['value' => '32.1507',   'operator' => 'mul']]],
                    ['code' => 'MARC',       'labels' => $makeLabels('marc'),       'symbol' => 'marc',   'convert_from_standard' => [['value' => '4.07754',   'operator' => 'mul']]],
                    ['code' => 'LIVRE',      'labels' => $makeLabels('livre'),      'symbol' => 'livre',  'convert_from_standard' => [['value' => '2.03877',   'operator' => 'mul']]],
                    ['code' => 'OUNCE',      'labels' => $makeLabels('ounce'),      'symbol' => 'oz',     'convert_from_standard' => [['value' => '35.274',    'operator' => 'mul']]],
                    ['code' => 'POUND',      'labels' => $makeLabels('pound'),      'symbol' => 'lb',     'convert_from_standard' => [['value' => '2.20462',   'operator' => 'mul']]],
                ],
            ],

            /*
            |------------------------------------------------------------------
            | 23. ANGLE (standard: Radian)
            |------------------------------------------------------------------
            */
            [
                'code'          => 'Angle',
                'name'          => 'Angle',
                'labels'        => $makeLabels('angle'),
                'standard_unit' => 'RADIAN',
                'symbol'        => 'rad',
                'units'         => [
                    ['code' => 'DEGREE',        'labels' => $makeLabels('degree'),        'symbol' => '°',   'convert_from_standard' => [['value' => '57.2958',    'operator' => 'mul']]],
                    ['code' => 'RADIAN',       'labels' => $makeLabels('radian'),       'symbol' => 'rad', 'convert_from_standard' => [['value' => '1',          'operator' => 'mul']]],
                    ['code' => 'GRADIAN',      'labels' => $makeLabels('gradian'),      'symbol' => 'grad', 'convert_from_standard' => [['value' => '63.662',     'operator' => 'mul']]],
                    ['code' => 'MINUTE_ANGLE', 'labels' => $makeLabels('minute angle'),   'symbol' => "'",   'convert_from_standard' => [['value' => '3437.75',    'operator' => 'mul']]],
                    ['code' => 'SECOND_ANGLE', 'labels' => $makeLabels('second angle'),   'symbol' => '"',    'convert_from_standard' => [['value' => '206265',     'operator' => 'mul']]],
                    ['code' => 'REVOLUTION',   'labels' => $makeLabels('revolution'),   'symbol' => 'rev',  'convert_from_standard' => [['value' => '6.28319',    'operator' => 'div']]],
                    ['code' => 'RIGHT_ANGLE',  'labels' => $makeLabels('right angle'),  'symbol' => '∠',   'convert_from_standard' => [['value' => '6.28319',    'operator' => 'div']]],
                    ['code' => 'OCTANT',       'labels' => $makeLabels('octant'),       'symbol' => 'oct',  'convert_from_standard' => [['value' => '1.25664',    'operator' => 'div']]],
                    ['code' => 'SEXTANT',      'labels' => $makeLabels('sextant'),      'symbol' => 'sext', 'convert_from_standard' => [['value' => '1.0472',     'operator' => 'div']]],
                ],
            ],

        ];

        /*
        |----------------------------------------------------------------------
        | Insert all families using raw DB queries (no model)
        |----------------------------------------------------------------------
        */
        foreach ($families as $family) {
            $now = now();

            $existing = DB::table('measurement_families')
                ->where('code', $family['code'])
                ->first();

            if ($existing) {
                DB::table('measurement_families')
                    ->where('code', $family['code'])
                    ->update([
                        'name'          => $family['name'],
                        'labels'        => json_encode($family['labels']),
                        'standard_unit' => $family['standard_unit'],
                        'symbol'        => $family['symbol'],
                        'units'         => json_encode($family['units']),
                        'updated_at'    => $now,
                    ]);
            } else {
                DB::table('measurement_families')->insert([
                    'code'          => $family['code'],
                    'name'          => $family['name'],
                    'labels'        => json_encode($family['labels']),
                    'standard_unit' => $family['standard_unit'],
                    'symbol'        => $family['symbol'],
                    'units'         => json_encode($family['units']),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }
}

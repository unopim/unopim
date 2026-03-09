<?php

namespace Webkul\Measurement\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Measurement\Models\MeasurementFamily;

class MeasurementFamilySeeder extends Seeder
{
    public function run()
    {
        /*
        |--------------------------------------------------------------------------
        | LENGTH
        |--------------------------------------------------------------------------
        */

        MeasurementFamily::updateOrCreate(
            ['code' => 'Length'],
            [
                'name'          => 'Length',
                'labels'        => ['en_US' => 'Length'],
                'standard_unit' => 'METER',
                'symbol'        => 'm',
                'units'         => [
                    ['code' => 'METER', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm'],
                    ['code' => 'CENTIMETER', 'labels' => ['en_US' => 'Centimeter'], 'symbol' => 'cm'],
                    ['code' => 'MICROMATER', 'labels' => ['en_US' => 'Micrometer'], 'symbol' => 'um'],
                    ['code' => 'NAUTICAL MILE', 'labels' => ['en_US' => 'Nautical mile'], 'symbol' => 'nm'],
                    ['code' => 'MILLIMETER', 'labels' => ['en_US' => 'Millimeter'], 'symbol' => 'mm'],
                    ['code' => 'DECIMETER', 'labels' => ['en_US' => 'Decimeter'], 'symbol' => 'dm'],
                    ['code' => 'DEKAMETER', 'labels' => ['en_US' => 'Dekameter'], 'symbol' => 'dam'],
                    ['code' => 'HECTOMETER', 'labels' => ['en_US' => 'Hectometer'], 'symbol' => 'hm'],
                    ['code' => 'KILOMETER', 'labels' => ['en_US' => 'Kilometer'], 'symbol' => 'km'],
                    ['code' => 'MIL', 'labels' => ['en_US' => 'Mil'], 'symbol' => 'mil'],
                    ['code' => 'INCH', 'labels' => ['en_US' => 'Inch'], 'symbol' => 'cm'],
                    ['code' => 'FEET', 'labels' => ['en_US' => 'Feet'], 'symbol' => 'ft'],
                    ['code' => 'YARD', 'labels' => ['en_US' => 'Yard'], 'symbol' => 'yd'],
                    ['code' => 'CHAIN', 'labels' => ['en_US' => 'Chain'], 'symbol' => 'ch'],
                    ['code' => 'FURLONG', 'labels' => ['en_US' => 'Furlong'], 'symbol' => 'fur'],
                    ['code' => 'MILI', 'labels' => ['en_US' => 'Mili'], 'symbol' => 'mi'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | AREA
        |--------------------------------------------------------------------------
        */

        MeasurementFamily::updateOrCreate(
            ['code' => 'Area'],
            [
                'name'          => 'Area',
                'labels'        => ['en_US' => 'Area'],
                'standard_unit' => 'SQUARE_METER',
                'symbol'        => 'm²',
                'units'         => [
                    ['code' => 'SQUARE_METER', 'labels' => ['en_US' => 'square Meter'], 'symbol' => 'm²'],
                    ['code' => 'SQUARE_CENTIMETER', 'labels' => ['en_US' => 'square centimeter'], 'symbol' => 'cm²'],
                    ['code' => 'SQUARE_MILLIMETER', 'labels' => ['en_US' => 'square millimeter'], 'symbol' => 'mm²'],
                    ['code' => 'SQUARE_DECIMETER', 'labels' => ['en_US' => 'square deciimeter'], 'symbol' => 'dm²'],
                    ['code' => 'SQUARE_FEET', 'labels' => ['en_US' => 'square feet'], 'symbol' => 'ft²'],
                    ['code' => 'SQUARE_YARD', 'labels' => ['en_US' => 'square yard'], 'symbol' => 'yd²'],
                    ['code' => 'HECTARE', 'labels' => ['en_US' => 'hectare'], 'symbol' => 'ha'],
                    ['code' => 'CENTRIARE', 'labels' => ['en_US' => 'centriare'], 'symbol' => 'ca'],
                    ['code' => 'SQUARE_DEKAMETER', 'labels' => ['en_US' => 'square dekameter'], 'symbol' => 'dam²'],
                    ['code' => 'ARE', 'labels' => ['en_US' => 'are'], 'symbol' => 'a'],
                    ['code' => 'SQUARE_HECTOMETER', 'labels' => ['en_US' => 'square hectometer'], 'symbol' => 'hm²'],
                    ['code' => 'SQUARE_KILOMETER', 'labels' => ['en_US' => 'square kilometer'], 'symbol' => 'km²'],
                    ['code' => 'SQUARE_MIL', 'labels' => ['en_US' => 'square mil'], 'symbol' => 'km²'],
                    ['code' => 'SQUARE_INCH', 'labels' => ['en_US' => 'square inch'], 'symbol' => 'in²'],
                    ['code' => 'SQUARE_FOOT', 'labels' => ['en_US' => 'square foot'], 'symbol' => 'ft²'],
                    ['code' => 'ARPENT', 'labels' => ['en_US' => 'arpent'], 'symbol' => 'arpent'],
                    ['code' => 'ACRE', 'labels' => ['en_US' => 'Acre'], 'symbol' => 'A'],
                    ['code' => 'SQUARE_furlog', 'labels' => ['en_US' => 'Square furlog'], 'symbol' => 'fur²'],
                    ['code' => 'SQUARE_mile', 'labels' => ['en_US' => 'square mile'], 'symbol' => 'mi²'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | WEIGHT / MASS
        |--------------------------------------------------------------------------
        */

        MeasurementFamily::updateOrCreate(
            ['code' => 'Weight'],
            [
                'name'          => 'Weight',
                'labels'        => ['en_US' => 'Weight'],
                'standard_unit' => 'KILOGRAM',
                'symbol'        => 'kg',
                'units'         => [
                    ['code' => 'MILLIGRAM', 'labels' => ['en_US' => 'Milligram'], 'symbol' => 'mg'],
                    ['code' => 'GRAM', 'labels' => ['en_US' => 'Gram'], 'symbol' => 'g'],
                    ['code' => 'KILOGRAM', 'labels' => ['en_US' => 'Kilogram'], 'symbol' => 'kg'],
                    ['code' => 'TONNE', 'labels' => ['en_US' => 'Tonne'], 'symbol' => 't'],
                    ['code' => 'MICROGRAM', 'labels' => ['en_US' => 'microgram'], 'symbol' => 'μg'],
                    ['code' => 'TON', 'labels' => ['en_US' => 'ton'], 'symbol' => 't'],
                    ['code' => 'GRAIN', 'labels' => ['en_US' => 'grain'], 'symbol' => 'gr'],
                    ['code' => 'DENIER', 'labels' => ['en_US' => 'denier'], 'symbol' => 'denier'],
                    ['code' => 'ONCE', 'labels' => ['en_US' => 'once'], 'symbol' => 'once'],
                    ['code' => 'Marc', 'labels' => ['en_US' => 'marc'], 'symbol' => 'marc'],
                    ['code' => 'LIVRE', 'labels' => ['en_US' => 'livre'], 'symbol' => 'livre'],
                    ['code' => 'OUNCE', 'labels' => ['en_US' => 'ounce'], 'symbol' => 'μg'],
                    ['code' => 'POUND', 'labels' => ['en_US' => 'pound'], 'symbol' => 'lb'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | ANGLE
        |--------------------------------------------------------------------------
        */

        MeasurementFamily::updateOrCreate(
            ['code' => 'Angle'],
            [
                'name'          => 'Angle',
                'labels'        => ['en_US' => 'Angle'],
                'standard_unit' => 'Radian',
                'symbol'        => 'A',
                'units'         => [
                    ['code' => 'RADIAN', 'labels' => ['en_US' => 'radian'], 'symbol' => 'rad'],
                    ['code' => 'MILLIRADIAN', 'labels' => ['en_US' => 'milliradian'], 'symbol' => 'mrad'],
                    ['code' => 'MICRORADIAN', 'labels' => ['en_US' => 'microradian'], 'symbol' => 'µrad'],
                    ['code' => 'DEGREE', 'labels' => ['en_US' => 'degree'], 'symbol' => '°'],
                    ['code' => 'MINUTE', 'labels' => ['en_US' => 'minute'], 'symbol' => 'M'],
                    ['code' => 'SECOND', 'labels' => ['en_US' => 'second'], 'symbol' => '"'],
                    ['code' => 'GON', 'labels' => ['en_US' => 'gon'], 'symbol' => 'gon'],
                    ['code' => 'MIL', 'labels' => ['en_US' => 'mil'], 'symbol' => 'mil'],
                    ['code' => 'REVOLUTION', 'labels' => ['en_US' => 'revolutin'], 'symbol' => 'rev'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | BINARY
        |--------------------------------------------------------------------------
        */

        MeasurementFamily::updateOrCreate(
            ['code' => 'Binary'],
            [
                'name'          => 'Binary',
                'labels'        => ['en_US' => 'Binary'],
                'standard_unit' => 'Byte',
                'symbol'        => 'by',
                'units'         => [
                    ['code' => 'BYTE', 'labels' => ['en_US' => 'byte'], 'symbol' => 'B'],
                    ['code' => 'CHAR', 'labels' => ['en_US' => 'char'], 'symbol' => 'char'],
                    ['code' => 'KILOBIT', 'labels' => ['en_US' => 'kilobit'], 'symbol' => 'kbit'],
                    ['code' => 'MEGABIT', 'labels' => ['en_US' => 'megabit'], 'symbol' => 'mbit'],
                    ['code' => 'GIBABIT', 'labels' => ['en_US' => 'gibabit'], 'symbol' => 'b'],
                    ['code' => 'BIT', 'labels' => ['en_US' => 'bit'], 'symbol' => '"'],
                    ['code' => 'KILOBYTE', 'labels' => ['en_US' => 'kilobyte'], 'symbol' => 'kb'],
                    ['code' => 'MAGABYTE', 'labels' => ['en_US' => 'magabyte'], 'symbol' => 'mb'],
                    ['code' => 'GIBABYTE', 'labels' => ['en_US' => 'gibabyte'], 'symbol' => 'gb'],
                    ['code' => 'TERABYTE', 'labels' => ['en_US' => 'terabyte'], 'symbol' => 'tb'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Brightness
        |--------------------------------------------------------------------------
        */

        MeasurementFamily::updateOrCreate(
            ['code' => 'Brightness'],
            [
                'name'          => 'Brightness',
                'labels'        => ['en_US' => 'Brightness'],
                'standard_unit' => 'LUMIN',
                'symbol'        => 'B',
                'units'         => [
                    ['code' => 'LUMIN', 'labels' => ['en_US' => 'Lumin'], 'symbol' => 'lm'],
                    ['code' => 'NIT', 'labels' => ['en_US' => 'Nit'], 'symbol' => 'nits'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Capacitance
        |--------------------------------------------------------------------------
        */

        MeasurementFamily::updateOrCreate(
            ['code' => 'Capacitance'],
            [
                'name'          => 'Capacitance',
                'labels'        => ['en_US' => 'Capacitance'],
                'standard_unit' => 'FARAD',
                'symbol'        => 'CA',
                'units'         => [
                    ['code' => 'FARAD', 'labels' => ['en_US' => 'Farad'], 'symbol' => 'F'],
                    ['code' => 'KILOFARAD', 'labels' => ['en_US' => 'Kilofarad'], 'symbol' => 'kF'],
                    ['code' => 'ATTOFARAD', 'labels' => ['en_US' => 'Attofarad'], 'symbol' => 'aF'],
                    ['code' => 'PICOFARAD', 'labels' => ['en_US' => 'Picofarad'], 'symbol' => 'pF'],
                    ['code' => 'NANOFARAD', 'labels' => ['en_US' => 'Nanofarad'], 'symbol' => 'nF'],
                    ['code' => 'MICROFARAD', 'labels' => ['en_US' => 'Microfarad'], 'symbol' => 'µF'],
                    ['code' => 'MILLIFARAD', 'labels' => ['en_US' => 'Millifarad'], 'symbol' => 'mF'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Decibel
        |--------------------------------------------------------------------------
        */

        MeasurementFamily::updateOrCreate(
            ['code' => 'Decibel'],
            [
                'name'          => 'Decibel',
                'labels'        => ['en_US' => 'Decibel'],
                'standard_unit' => 'DECIBEL',
                'symbol'        => 'DE',
                'units'         => [
                    ['code' => 'DECIBEL', 'labels' => ['en_US' => 'Decibel'], 'symbol' => 'D'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Duration
        |--------------------------------------------------------------------------
        */

        MeasurementFamily::updateOrCreate(
            ['code' => 'Duration'],
            [
                'name'          => 'Duration',
                'labels'        => ['en_US' => 'Duration'],
                'standard_unit' => 'SECOND',
                'symbol'        => 'D',
                'units'         => [
                    ['code' => 'SECOND', 'labels' => ['en_US' => 'Second'], 'symbol' => 'S'],
                    ['code' => 'MILLISECOND', 'labels' => ['en_US' => 'Millisecond'], 'symbol' => 'ms'],
                    ['code' => 'MINUTE', 'labels' => ['en_US' => 'Minute'], 'symbol' => 'M'],
                    ['code' => 'HOUR', 'labels' => ['en_US' => 'Hour'], 'symbol' => 'H'],
                    ['code' => 'DAY', 'labels' => ['en_US' => 'Day'], 'symbol' => 'd'],
                    ['code' => 'WEEK', 'labels' => ['en_US' => 'Week'], 'symbol' => 'week'],
                    ['code' => 'MONTH', 'labels' => ['en_US' => 'Month'], 'symbol' => 'month'],
                    ['code' => 'YEAR', 'labels' => ['en_US' => 'Year'], 'symbol' => 'year'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Volume flow
        |--------------------------------------------------------------------------
        */

        MeasurementFamily::updateOrCreate(
            ['code' => 'Volume flow'],
            [
                'name'          => 'Volume flow',
                'labels'        => ['en_US' => 'Volume flow'],
                'standard_unit' => 'CUBIC_METER_PER_SECOND',
                'symbol'        => 'm³',
                'units'         => [
                    ['code' => 'CUBIC_METER_PER_SECOND', 'labels' => ['en_US' => 'Cubic meter per second'], 'symbol' => 'm³/s'],
                    ['code' => 'CUBIC_METER_PER_MINUTE', 'labels' => ['en_US' => 'Cubic meter per minute'], 'symbol' => 'm³/min'],
                    ['code' => 'CUBIC_METER_PER_HOUR', 'labels' => ['en_US' => 'Cubic meter per hour'], 'symbol' => 'm³/h'],
                    ['code' => 'CUBIC_METER_PER_DAY', 'labels' => ['en_US' => 'Cubic meter per day'], 'symbol' => 'm³/d'],
                    ['code' => 'MILLILITER_PER_SECOND', 'labels' => ['en_US' => 'Milliliter per second'], 'symbol' => 'ml/s'],
                    ['code' => 'MILLILITER_PER_MINUTE', 'labels' => ['en_US' => 'Milliliter per minute'], 'symbol' => 'ml/min'],
                    ['code' => 'MILLILITER_PER_HOUR', 'labels' => ['en_US' => 'Milliliter per hour'], 'symbol' => 'ml/h'],
                    ['code' => 'MILLILITER_PER_DAY', 'labels' => ['en_US' => 'Milliliter per day'], 'symbol' => 'ml/d'],
                    ['code' => 'CUBIC_CENTIMETER_PER_SECOND', 'labels' => ['en_US' => 'Cubic centimeter per second'], 'symbol' => 'cm³/s'],
                    ['code' => 'CUBIC_CENTIMETER_PER_MINUTE', 'labels' => ['en_US' => 'Cubic centimeter per minute'], 'symbol' => 'cm³/min'],
                    ['code' => 'CUBIC_CENTIMETER_PER_HOUR', 'labels' => ['en_US' => 'Cubic centimeter per hour'], 'symbol' => 'cm³/h'],
                    ['code' => 'CUBIC_CENTIMETER_PER_DAY', 'labels' => ['en_US' => 'Cubic centimeter per day'], 'symbol' => 'cm³/d'],
                    ['code' => 'CUBIC_DECIMETER_PER_MINUTE', 'labels' => ['en_US' => 'Cubic decimeter per minute'], 'symbol' => 'dm³/min'],
                    ['code' => 'CUBIC_DECIMETER_PER_HOUR', 'labels' => ['en_US' => 'Cubic decimeter per hour'], 'symbol' => 'dm³/h'],
                    ['code' => 'LITER_PER_SECOND', 'labels' => ['en_US' => 'Liter per second'], 'symbol' => 'l/s'],
                    ['code' => 'LITER_PER_MINUTE', 'labels' => ['en_US' => 'Liter per minute'], 'symbol' => 'l/min'],
                    ['code' => 'LITER_PER_HOUR', 'labels' => ['en_US' => 'Liter per hour'], 'symbol' => 'l/h'],
                    ['code' => 'LITER_PER_DAY', 'labels' => ['en_US' => 'Liter per day'], 'symbol' => 'l/d'],
                    ['code' => 'KILOLITER_PER_HOUR', 'labels' => ['en_US' => 'Kiloliter per hour'], 'symbol' => 'kl/h'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | VOLUME
        |--------------------------------------------------------------------------
        */
        MeasurementFamily::updateOrCreate(
            ['code' => 'volume'],
            [
                'name'          => 'Volume',
                'labels'        => ['en_US' => 'Volume'],
                'standard_unit' => 'LITER',
                'symbol'        => 'L',
                'units'         => [
                    ['code' => 'CUBIC_METER',        'labels' => ['en_US' => 'Cubic meter'],        'symbol' => 'm³'],
                    ['code' => 'LITER',               'labels' => ['en_US' => 'Liter'],              'symbol' => 'L'],
                    ['code' => 'DECILITER',           'labels' => ['en_US' => 'Deciliter'],          'symbol' => 'dl'],
                    ['code' => 'CENTILITER',          'labels' => ['en_US' => 'Centiliter'],         'symbol' => 'cl'],
                    ['code' => 'MILLILITER',          'labels' => ['en_US' => 'Milliliter'],         'symbol' => 'ml'],

                    ['code' => 'CUBIC_DECIMETER',     'labels' => ['en_US' => 'Cubic decimeter'],    'symbol' => 'dm³'],
                    ['code' => 'CUBIC_CENTIMETER',    'labels' => ['en_US' => 'Cubic centimeter'],   'symbol' => 'cm³'],
                    ['code' => 'CUBIC_MILLIMETER',    'labels' => ['en_US' => 'Cubic millimeter'],   'symbol' => 'mm³'],

                    ['code' => 'GALLON',              'labels' => ['en_US' => 'Gallon'],             'symbol' => 'gal'],
                    ['code' => 'QUART',               'labels' => ['en_US' => 'Quart'],              'symbol' => 'qt'],
                    ['code' => 'PINT',                'labels' => ['en_US' => 'Pint'],               'symbol' => 'pt'],
                    ['code' => 'CUP',                 'labels' => ['en_US' => 'Cup'],                'symbol' => 'cup'],

                    ['code' => 'FLUID_OUNCE',          'labels' => ['en_US' => 'Fluid ounce'],        'symbol' => 'fl oz'],
                    ['code' => 'TABLESPOON',           'labels' => ['en_US' => 'Tablespoon'],         'symbol' => 'tbsp'],
                    ['code' => 'TEASPOON',             'labels' => ['en_US' => 'Teaspoon'],           'symbol' => 'tsp'],

                    ['code' => 'KILOLITER',            'labels' => ['en_US' => 'Kiloliter'],          'symbol' => 'kL'],
                    ['code' => 'HECTOLITER',           'labels' => ['en_US' => 'Hectoliter'],         'symbol' => 'hL'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | VOLTAGE
        |--------------------------------------------------------------------------
        */
        MeasurementFamily::updateOrCreate(
            ['code' => 'voltage'],
            [
                'name'          => 'Voltage',
                'labels'        => ['en_US' => 'Voltage'],
                'standard_unit' => 'VOLT',
                'symbol'        => 'V',
                'units'         => [
                    ['code' => 'MICROVOLT', 'labels' => ['en_US' => 'Microvolt'], 'symbol' => 'µV'],
                    ['code' => 'MILLIVOLT', 'labels' => ['en_US' => 'Millivolt'], 'symbol' => 'mV'],
                    ['code' => 'VOLT',      'labels' => ['en_US' => 'Volt'],      'symbol' => 'V'],
                    ['code' => 'KILOVOLT',  'labels' => ['en_US' => 'Kilovolt'],  'symbol' => 'kV'],
                    ['code' => 'MEGAVOLT',  'labels' => ['en_US' => 'Megavolt'],  'symbol' => 'MV'],
                    ['code' => 'GIGAVOLT',  'labels' => ['en_US' => 'Gigavolt'],  'symbol' => 'GV'],
                    ['code' => 'TERAVOLT',  'labels' => ['en_US' => 'Teravolt'],  'symbol' => 'TV'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | TEMPERATURE
        |--------------------------------------------------------------------------
        */
        MeasurementFamily::updateOrCreate(
            ['code' => 'temperature'],
            [
                'name'          => 'Temperature',
                'labels'        => ['en_US' => 'Temperature'],
                'standard_unit' => 'CELSIUS',
                'symbol'        => '°C',
                'units'         => [
                    ['code' => 'CELSIUS',    'labels' => ['en_US' => 'Celsius'],    'symbol' => '°C'],
                    ['code' => 'FAHRENHEIT', 'labels' => ['en_US' => 'Fahrenheit'], 'symbol' => '°F'],
                    ['code' => 'KELVIN',     'labels' => ['en_US' => 'Kelvin'],     'symbol' => 'K'],
                    ['code' => 'RANKINE',    'labels' => ['en_US' => 'Rankine'],    'symbol' => '°R'],
                    ['code' => 'REAUMUR',    'labels' => ['en_US' => 'Réaumur'],    'symbol' => '°Ré'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | SPEED
        |--------------------------------------------------------------------------
        */
        MeasurementFamily::updateOrCreate(
            ['code' => 'speed'],
            [
                'name'          => 'Speed',
                'labels'        => ['en_US' => 'Speed'],
                'standard_unit' => 'METER_PER_SECOND',
                'symbol'        => 'm/s',
                'units'         => [
                    ['code' => 'METER_PER_SECOND',     'labels' => ['en_US' => 'Meter per second'],     'symbol' => 'm/s'],
                    ['code' => 'KILOMETER_PER_HOUR',   'labels' => ['en_US' => 'Kilometer per hour'],   'symbol' => 'km/h'],
                    ['code' => 'MILE_PER_HOUR',        'labels' => ['en_US' => 'Mile per hour'],        'symbol' => 'mph'],
                    ['code' => 'FOOT_PER_SECOND',      'labels' => ['en_US' => 'Foot per second'],      'symbol' => 'ft/s'],
                    ['code' => 'KNOT',                 'labels' => ['en_US' => 'Knot'],                 'symbol' => 'kn'],
                    ['code' => 'MACH',                 'labels' => ['en_US' => 'Mach'],                 'symbol' => 'Mach'],
                    ['code' => 'CENTIMETER_PER_SECOND', 'labels' => ['en_US' => 'Centimeter per second'], 'symbol' => 'cm/s'],
                    ['code' => 'KILOMETER_PER_SECOND', 'labels' => ['en_US' => 'Kilometer per second'], 'symbol' => 'km/s'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | RESISTANCE
        |--------------------------------------------------------------------------
        */
        MeasurementFamily::updateOrCreate(
            ['code' => 'resistance'],
            [
                'name'          => 'Resistance',
                'labels'        => ['en_US' => 'Resistance'],
                'standard_unit' => 'OHM',
                'symbol'        => 'Ω',
                'units'         => [
                    ['code' => 'MICROOHM', 'labels' => ['en_US' => 'Microohm'], 'symbol' => 'µΩ'],
                    ['code' => 'MILLIOHM', 'labels' => ['en_US' => 'Milliohm'], 'symbol' => 'mΩ'],
                    ['code' => 'OHM',      'labels' => ['en_US' => 'Ohm'],      'symbol' => 'Ω'],
                    ['code' => 'KILOOHM',  'labels' => ['en_US' => 'Kiloohm'],  'symbol' => 'kΩ'],
                    ['code' => 'MEGAOHM',  'labels' => ['en_US' => 'Megaohm'],  'symbol' => 'MΩ'],
                    ['code' => 'GIGAOHM',  'labels' => ['en_US' => 'Gigaohm'],  'symbol' => 'GΩ'],
                    ['code' => 'TERAOHM',  'labels' => ['en_US' => 'Teraohm'],  'symbol' => 'TΩ'],
                    ['code' => 'NANOOHM',  'labels' => ['en_US' => 'Nanoohm'],  'symbol' => 'nΩ'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | PRESSURE
        |--------------------------------------------------------------------------
        */
        MeasurementFamily::updateOrCreate(
            ['code' => 'pressure'],
            [
                'name'          => 'Pressure',
                'labels'        => ['en_US' => 'Pressure'],
                'standard_unit' => 'PASCAL',
                'symbol'        => 'Pa',
                'units'         => [
                    ['code' => 'PASCAL',          'labels' => ['en_US' => 'Pascal'],           'symbol' => 'Pa'],
                    ['code' => 'HECTOPASCAL',     'labels' => ['en_US' => 'Hectopascal'],      'symbol' => 'hPa'],
                    ['code' => 'KILOPASCAL',      'labels' => ['en_US' => 'Kilopascal'],       'symbol' => 'kPa'],
                    ['code' => 'MEGAPASCAL',      'labels' => ['en_US' => 'Megapascal'],       'symbol' => 'MPa'],
                    ['code' => 'BAR',             'labels' => ['en_US' => 'Bar'],              'symbol' => 'bar'],
                    ['code' => 'MILLIBAR',        'labels' => ['en_US' => 'Millibar'],         'symbol' => 'mbar'],
                    ['code' => 'ATMOSPHERE',      'labels' => ['en_US' => 'Atmosphere'],       'symbol' => 'atm'],
                    ['code' => 'TORR',            'labels' => ['en_US' => 'Torr'],             'symbol' => 'Torr'],
                    ['code' => 'PSI',             'labels' => ['en_US' => 'Pound per square inch'], 'symbol' => 'psi'],
                    ['code' => 'MMHG',            'labels' => ['en_US' => 'Millimeter of mercury'],  'symbol' => 'mmHg'],
                    ['code' => 'INHG',            'labels' => ['en_US' => 'Inch of mercury'],  'symbol' => 'inHg'],
                    ['code' => 'KGF_PER_CM2',     'labels' => ['en_US' => 'Kilogram-force per square centimeter'], 'symbol' => 'kgf/cm²'],
                    ['code' => 'KGF_PER_M2',      'labels' => ['en_US' => 'Kilogram-force per square meter'],      'symbol' => 'kgf/m²'],
                    ['code' => 'DYNE_PER_CM2',    'labels' => ['en_US' => 'Dyne per square centimeter'],           'symbol' => 'dyn/cm²'],
                    ['code' => 'FOOT_WATER',      'labels' => ['en_US' => 'Foot of water'],     'symbol' => 'ftH₂O'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | POWER
        |--------------------------------------------------------------------------
        */
        MeasurementFamily::updateOrCreate(
            ['code' => 'power'],
            [
                'name'          => 'Power',
                'labels'        => ['en_US' => 'Power'],
                'standard_unit' => 'WATT',
                'symbol'        => 'W',
                'units'         => [
                    ['code' => 'MILLIWATT', 'labels' => ['en_US' => 'Milliwatt'], 'symbol' => 'mW'],
                    ['code' => 'WATT',      'labels' => ['en_US' => 'Watt'],      'symbol' => 'W'],
                    ['code' => 'KILOWATT',  'labels' => ['en_US' => 'Kilowatt'],  'symbol' => 'kW'],
                    ['code' => 'MEGAWATT',  'labels' => ['en_US' => 'Megawatt'],  'symbol' => 'MW'],
                    ['code' => 'GIGAWATT',  'labels' => ['en_US' => 'Gigawatt'],  'symbol' => 'GW'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | ELECTRIC CHARGE
        |--------------------------------------------------------------------------
        */
        MeasurementFamily::updateOrCreate(
            ['code' => 'electric_charge'],
            [
                'name'          => 'Electric Charge',
                'labels'        => ['en_US' => 'Electric Charge'],
                'standard_unit' => 'COULOMB',
                'symbol'        => 'C',
                'units'         => [
                    ['code' => 'PICCOULOMB',  'labels' => ['en_US' => 'Picocoulomb'],  'symbol' => 'pC'],
                    ['code' => 'NANOCOULOMB', 'labels' => ['en_US' => 'Nanocoulomb'],  'symbol' => 'nC'],
                    ['code' => 'MICROCOULOMB', 'labels' => ['en_US' => 'Microcoulomb'], 'symbol' => 'µC'],
                    ['code' => 'MILLICOULOMB', 'labels' => ['en_US' => 'Millicoulomb'], 'symbol' => 'mC'],
                    ['code' => 'COULOMB',     'labels' => ['en_US' => 'Coulomb'],      'symbol' => 'C'],
                    ['code' => 'KILOCOULOMB', 'labels' => ['en_US' => 'Kilocoulomb'],  'symbol' => 'kC'],
                    ['code' => 'MEGACOULOMB', 'labels' => ['en_US' => 'Megacoulomb'],  'symbol' => 'MC'],
                    ['code' => 'AMPERE_HOUR', 'labels' => ['en_US' => 'Ampere hour'],  'symbol' => 'Ah'],
                    ['code' => 'MILLIAMPERE_HOUR', 'labels' => ['en_US' => 'Milliampere hour'], 'symbol' => 'mAh'],
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | INTENSITY
        |--------------------------------------------------------------------------
        */
        MeasurementFamily::updateOrCreate(
            ['code' => 'intensity'],
            [
                'name'          => 'Intensity',
                'labels'        => ['en_US' => 'Intensity'],
                'standard_unit' => 'CANDELA',
                'symbol'        => 'cd',
                'units'         => [
                    ['code' => 'MICROCANDELA', 'labels' => ['en_US' => 'Microcandela'], 'symbol' => 'µcd'],
                    ['code' => 'MILLICANDELA', 'labels' => ['en_US' => 'Millicandela'], 'symbol' => 'mcd'],
                    ['code' => 'CANDELA',      'labels' => ['en_US' => 'Candela'],      'symbol' => 'cd'],
                    ['code' => 'KILOCANDELA',  'labels' => ['en_US' => 'Kilocandela'],  'symbol' => 'kcd'],
                    ['code' => 'MEGACANDELA',  'labels' => ['en_US' => 'Megacandela'],  'symbol' => 'Mcd'],
                    ['code' => 'LUMEN_PER_SR', 'labels' => ['en_US' => 'Lumen per steradian'], 'symbol' => 'lm/sr'],
                    ['code' => 'HEFNERKERZE',  'labels' => ['en_US' => 'Hefnerkerze'],  'symbol' => 'HK'],
                ],
            ]
        );

    }
}

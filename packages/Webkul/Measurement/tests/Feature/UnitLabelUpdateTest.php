<?php

use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Measurement\Validation\MeasurementUnitValidator;

beforeEach(function () {
    $this->loginAsAdmin();
});

function seededKilowattHourLabels(): array
{
    return [
        'en_US' => 'Kilowatt hour',
        'sq_AL' => 'Kilowatt hour',
        'es_ES' => 'Kilovatio-hora',
        'pt_BR' => 'Quilowatt-hora',
        'ro_RO' => 'Kilowatt-oră',
        'ru_RU' => 'Киловатт-час',
        'tr_TR' => 'Kilovat-saat',
        'tl_PH' => 'Kilowatt-oras',
        'uk_UA' => 'Кіловат-година',
    ];
}

function energyFamily(array $labels): MeasurementFamily
{
    return MeasurementFamily::factory()->create([
        'code'          => 'Energy_'.uniqid(),
        'standard_unit' => 'JOULE',
        'units'         => [
            [
                'code'                  => 'JOULE',
                'labels'                => ['en_US' => 'Joule'],
                'symbol'                => 'J',
                'convert_from_standard' => [['operator' => 'mul', 'value' => '1']],
            ],
            [
                'code'                  => 'KILOWATTHOUR',
                'labels'                => $labels,
                'symbol'                => 'kWh',
                'convert_from_standard' => [['operator' => 'div', 'value' => '3600000']],
            ],
        ],
    ]);
}

function activeLocaleCodes(): array
{
    $codes = app(LocaleRepository::class)->getActiveLocales()->pluck('code')->all();

    expect($codes)->not->toBeEmpty();

    return $codes;
}

function unitModalMarkup(int $familyId): string
{
    $html = test()->get(route('admin.measurement.families.edit', $familyId))
        ->assertOk()
        ->getContent();

    preg_match('#<script type="text/x-template" id="v-locales-template">(.*?)</script>#s', $html, $matches);

    return $matches[1] ?? '';
}

function unitModalErrorKeyMapper(): Closure
{
    $blade = file_get_contents(
        base_path('packages/Webkul/Measurement/src/Resources/views/measurement-families/edit.blade.php')
    );

    expect($blade)->toContain('setErrors(this.toFieldNameKeys(error.response.data.errors))');

    preg_match("#key\.replace\(/(.+?)/g,\s*'(.+?)'\)#", $blade, $matches);

    expect($matches)->toHaveCount(3);

    return fn (string $key): string => preg_replace('/'.$matches[1].'/', $matches[2], $key);
}

describe('unit label update', function () {
    it('accepts the exact payload the unit edit modal submits when only the Albania label is changed', function () {
        $family = energyFamily(seededKilowattHourLabels());

        $labels = seededKilowattHourLabels();
        $labels['sq_AL'] = 'Kilovat ore';

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
        ])->put(
            route('admin.measurement.families.units.update', [
                'familyId' => $family->id,
                'code'     => 'KILOWATTHOUR',
            ]),
            [
                'code'                  => 'KILOWATTHOUR',
                'labels'                => $labels,
                'symbol'                => 'kWh',
                'convert_value'         => ['3600000'],
                'convert_from_standard' => ['div'],
            ]
        );

        $response->assertOk();

        expect($response->json('errors'))->toBeNull();

        $family->refresh();

        $stored = collect($family->units)->firstWhere('code', 'KILOWATTHOUR')['labels'];

        expect($stored['sq_AL'])->toBe('Kilovat ore')
            ->and($stored)->toEqual($labels);
    });

    it('persists every hyphenated locale the modal resubmits alongside the edited one', function () {
        $family = energyFamily(seededKilowattHourLabels());

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
        ])->put(
            route('admin.measurement.families.units.update', [
                'familyId' => $family->id,
                'code'     => 'KILOWATTHOUR',
            ]),
            ['labels' => seededKilowattHourLabels()]
        )
            ->assertOk()
            ->assertJsonMissingPath('errors');

        $family->refresh();

        expect(collect($family->units)->firstWhere('code', 'KILOWATTHOUR')['labels'])
            ->toEqual(seededKilowattHourLabels());
    });

    it('is not locale specific: any locale whose value carries a hyphen is stored, including the current one', function (string $locale) {
        $family = energyFamily(['en_US' => 'Kilowatt hour']);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
        ])->put(
            route('admin.measurement.families.units.update', [
                'familyId' => $family->id,
                'code'     => 'KILOWATTHOUR',
            ]),
            ['labels' => [$locale => 'Kilowatt-hour']]
        )
            ->assertOk()
            ->assertJsonMissingPath('errors');

        $family->refresh();

        expect(collect($family->units)->firstWhere('code', 'KILOWATTHOUR')['labels'][$locale])
            ->toBe('Kilowatt-hour');
    })->with(['en_US', 'sq_AL', 'fr_FR']);

    it('accepts the very same Albania label once the hyphen is removed', function () {
        $family = energyFamily(['en_US' => 'Kilowatt hour']);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
        ])->put(
            route('admin.measurement.families.units.update', [
                'familyId' => $family->id,
                'code'     => 'KILOWATTHOUR',
            ]),
            ['labels' => ['sq_AL' => 'Kilovat ore']]
        )
            ->assertOk();

        $family->refresh();

        expect(collect($family->units)->firstWhere('code', 'KILOWATTHOUR')['labels']['sq_AL'])
            ->toBe('Kilovat ore');
    });

    it('proves symbol and conversion are not required for a label only update', function () {
        $family = energyFamily(['en_US' => 'Kilowatt hour']);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
        ])->put(
            route('admin.measurement.families.units.update', [
                'familyId' => $family->id,
                'code'     => 'KILOWATTHOUR',
            ]),
            ['labels' => ['sq_AL' => 'Kilovat ore']]
        )
            ->assertOk();

        $family->refresh();

        $unit = collect($family->units)->firstWhere('code', 'KILOWATTHOUR');

        expect($unit['symbol'])->toBe('kWh')
            ->and($unit['convert_from_standard'])->toEqual([['operator' => 'div', 'value' => '3600000']]);
    });

    it('also allows creating a unit whose label contains a hyphen', function () {
        $family = energyFamily(['en_US' => 'Kilowatt hour']);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
        ])->post(
            route('admin.measurement.families.units.store', $family->id),
            [
                'code'   => 'MEGAWATTHOUR',
                'symbol' => 'MWh',
                'labels' => ['en_US' => 'Megawatt-hour'],
            ]
        )
            ->assertOk()
            ->assertJsonMissingPath('errors');

        $family->refresh();

        expect(collect($family->units)->firstWhere('code', 'MEGAWATTHOUR')['labels']['en_US'])
            ->toBe('Megawatt-hour');
    });

    it('still blocks creating a unit whose label carries a character outside the label set', function () {
        $family = energyFamily(['en_US' => 'Kilowatt hour']);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
        ])->post(
            route('admin.measurement.families.units.store', $family->id),
            [
                'code'   => 'MEGAWATTHOUR',
                'symbol' => 'MWh',
                'labels' => ['en_US' => 'Megawatt<hour>'],
            ]
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['labels.en_US']);

        $family->refresh();

        expect(collect($family->units)->firstWhere('code', 'MEGAWATTHOUR'))->toBeNull();
    });
});

describe('unit label rejection reporting', function () {
    it('reports a genuine rejection under dotted keys and leaves the stored labels alone', function () {
        $family = energyFamily(seededKilowattHourLabels());

        $labels = seededKilowattHourLabels();
        $labels['es_ES'] = 'Kilovatio#hora';

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept'           => 'application/json',
        ])->put(
            route('admin.measurement.families.units.update', [
                'familyId' => $family->id,
                'code'     => 'KILOWATTHOUR',
            ]),
            ['labels' => $labels]
        );

        $response->assertStatus(422);

        $body = $response->json();

        expect($body['message'])->toBe(trans('measurement::app.validation.label_format'))
            ->and($body['message'])->toBe(
                'This field may only contain letters, numbers, spaces, and the characters _ - \' . , / ( ).'
            )
            ->and($body['errors'])->toBe([
                'labels.es_ES' => [trans('measurement::app.validation.label_format')],
            ])
            ->and($body['errors'])->not->toHaveKey('labels[es_ES]')
            ->and($body['errors'])->not->toHaveKey('symbol')
            ->and($body['errors'])->not->toHaveKey('convert_value');

        $family->refresh();

        expect(collect($family->units)->firstWhere('code', 'KILOWATTHOUR')['labels'])
            ->toEqual(seededKilowattHourLabels());
    });

    it('maps every dotted error key onto an error slot the edit modal actually renders', function () {
        $family = energyFamily(seededKilowattHourLabels());

        $locales = activeLocaleCodes();

        $errorKeys = array_keys(
            $this->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept'           => 'application/json',
            ])->put(
                route('admin.measurement.families.units.update', [
                    'familyId' => $family->id,
                    'code'     => 'KILOWATTHOUR',
                ]),
                ['labels' => array_fill_keys($locales, 'Kilowatt#hour')]
            )
                ->assertStatus(422)
                ->json('errors')
        );

        expect($errorKeys)->toEqual(array_map(fn (string $code): string => 'labels.'.$code, $locales));

        $mapper = unitModalErrorKeyMapper();
        $markup = unitModalMarkup($family->id);

        foreach ($errorKeys as $key) {
            $fieldName = $mapper($key);

            expect($fieldName)->toBe('labels['.explode('.', $key)[1].']')
                ->and($markup)->toContain('data-error-slot="'.$fieldName.'"');
        }
    });

    it('renders a label error slot for every active locale in the unit modal', function () {
        $family = energyFamily(['en_US' => 'Kilowatt hour']);

        $markup = unitModalMarkup($family->id);

        foreach (activeLocaleCodes() as $code) {
            expect($markup)->toContain('data-error-slot="labels['.$code.']"');
        }
    });
});

describe('seeded unit labels against the update validator', function () {
    it('accepts the kilowatt hour translations the seeder ships', function () {
        $results = [];

        foreach (['es_ES', 'pt_BR', 'pt_PT', 'ro_RO', 'ru_RU', 'tr_TR', 'tl_PH', 'uk_UA'] as $locale) {
            $translated = trans('measurement::app.seeder.kilowatt_hour', [], $locale);

            $results[$locale] = [
                'label'    => $translated,
                'accepted' => ! validator(
                    ['labels' => [$locale => $translated]],
                    MeasurementUnitValidator::updateRules()
                )->fails(),
            ];
        }

        expect($results)->toBe([
            'es_ES' => ['label' => 'Kilovatio-hora', 'accepted' => true],
            'pt_BR' => ['label' => 'Quilowatt-hora', 'accepted' => true],
            'pt_PT' => ['label' => 'Quilowatt-hora', 'accepted' => true],
            'ro_RO' => ['label' => 'Kilowatt-oră', 'accepted' => true],
            'ru_RU' => ['label' => 'Киловатт-час', 'accepted' => true],
            'tr_TR' => ['label' => 'Kilovat-saat', 'accepted' => true],
            'tl_PH' => ['label' => 'Kilowatt-oras', 'accepted' => true],
            'uk_UA' => ['label' => 'Кіловат-година', 'accepted' => true],
        ]);
    });

    it('accepts the punctuation that real unit names need', function (string $label) {
        $validator = validator(
            ['labels' => ['en_US' => $label]],
            MeasurementUnitValidator::updateRules()
        );

        expect($validator->fails())->toBeFalse();
    })->with([
        'Kilowatt-hour',
        'Metre/second',
        'Foot-pound',
        "Ohm's law unit",
        'Volt.ampere',
        'Kilovatio-hora',
        'Mil·limetre',
        'Об’єм',
        'Foot, US',
        'Degree Celsius (C)',
    ]);

    it('still rejects characters that have no place in a label', function (string $label) {
        $validator = validator(
            ['labels' => ['en_US' => $label]],
            MeasurementUnitValidator::updateRules()
        );

        expect($validator->fails())->toBeTrue();
    })->with([
        'Degree Celsius (°C)',
        '<script>alert(1)</script>',
        'label#hash',
        'unit@example.com',
        '$dollar',
        'semi;colon',
        'pipe|value',
        'brace{value}',
        'double"quote',
        '123',
    ]);
});

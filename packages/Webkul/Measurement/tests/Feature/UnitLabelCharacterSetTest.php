<?php

use Webkul\Measurement\Validation\MeasurementFamilyValidator;
use Webkul\Measurement\Validation\MeasurementUnitValidator;

function labelRuleOf(string $validator): string
{
    $constant = new ReflectionClassConstant($validator, 'LABEL_REGEX');

    return (string) $constant->getValue();
}

function labelPattern(): string
{
    return str_replace('regex:', '', labelRuleOf(MeasurementUnitValidator::class));
}

function seederLabels(): array
{
    $labels = [];

    foreach (glob(base_path('packages/Webkul/Measurement/src/Resources/lang/*'), GLOB_ONLYDIR) as $dir) {
        $file = $dir.'/app.php';

        if (! file_exists($file)) {
            continue;
        }

        foreach ((include $file)['seeder'] ?? [] as $key => $value) {
            if (is_string($value) && $value !== '') {
                $labels[basename($dir).'.'.$key] = $value;
            }
        }
    }

    return $labels;
}

it('accepts every label the seeder ships', function () {
    $rejected = collect(seederLabels())
        ->reject(fn (string $label): bool => (bool) preg_match(labelPattern(), $label))
        ->take(10)
        ->all();

    expect($rejected)->toBe([]);
});

it('scans the whole seeded label set rather than a sample', function () {
    expect(count(seederLabels()))->toBeGreaterThan(6000);
});

it('accepts the punctuation real unit names carry', function (string $label) {
    expect(preg_match(labelPattern(), $label))->toBe(1);
})->with([
    'Kilovatio-hora',
    'Квіловат-час',
    'Mil·limetre',
    "metro all'ora",
    'Об’єм',
    '分(角度)',
    'km/h',
    'Foot, US',
    'Pinta-ala',
]);

it('still rejects everything that has no place in a label', function (string $label) {
    expect(preg_match(labelPattern(), $label))->toBe(0);
})->with([
    '<script>alert(1)</script>',
    'label#hash',
    'unit@example.com',
    '$dollar',
    'semi;colon',
    'pipe|value',
    'brace{value}',
    '',
    '123',
]);

it('applies the same character set to family labels and names', function () {
    expect(labelRuleOf(MeasurementFamilyValidator::class))->toBe(labelRuleOf(MeasurementUnitValidator::class));
});

it('records that the family validator applies the rule to only some of its rule sets', function () {
    $applied = collect([
        'storeRules'     => MeasurementFamilyValidator::storeRules(),
        'updateRules'    => MeasurementFamilyValidator::updateRules(),
        'apiStoreRules'  => MeasurementFamilyValidator::apiStoreRules(),
        'apiUpdateRules' => MeasurementFamilyValidator::apiUpdateRules(1),
    ])->map(fn (array $rules): bool => collect($rules['labels.*'] ?? [])
        ->contains(fn ($rule): bool => str_starts_with((string) $rule, 'regex:')));

    expect($applied->all())->toBe([
        'storeRules'     => true,
        'updateRules'    => false,
        'apiStoreRules'  => false,
        'apiUpdateRules' => true,
    ]);
});

it('leaves the stricter code rule untouched', function () {
    $codeRule = collect(MeasurementUnitValidator::storeRules()['code'])
        ->first(fn ($rule): bool => str_starts_with((string) $rule, 'regex:'));

    expect($codeRule)->toBe('regex:/^[A-Za-z0-9_]+$/u');
});

<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Product\Enums\ProductType;

beforeEach(function (): void {
    $this->loginAsAdmin();

    $cache = new ReflectionProperty(Importer::class, 'staticInitCache');
    $cache->setValue(null, null);
});

/**
 * @param  array<string, mixed>  $scope
 * @return array{0: AttributeFamily, 1: Attribute, 2: Attribute}
 */
function familyWithTwoUniqueAttributes(array $scope = []): array
{
    $first = Attribute::factory()->create(['type' => 'text', 'is_unique' => 1, ...$scope]);
    $second = Attribute::factory()->create(['type' => 'text', 'is_unique' => 1, ...$scope]);

    $family = AttributeFamily::factory()->create();
    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    $family->refresh();
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', ['sku', 'status'])->get());
    AttributeFamily::factory()->linkAttributesToFamily($family, collect([$first, $second]));

    return [$family->fresh(), $first, $second];
}

function uniqueValuesImporter(): Importer
{
    $importer = app(Importer::class)->setImport(JobTrack::factory()->create());
    $importer->setErrorHelper(app(Error::class));

    return $importer;
}

/**
 * @param  array<string, string>  $values
 * @return array<string, string>
 */
function uniqueValuesRow(AttributeFamily $family, array $values): array
{
    return [
        'sku'              => 'unique-row-'.uniqid(),
        'type'             => ProductType::Simple->value,
        'attribute_family' => $family->code,
        'channel'          => 'default',
        'locale'           => 'en_US',
        'status'           => 'true',
        ...$values,
    ];
}

it('rejects an in-file duplicate of any unique attribute, not only the last one checked', function (): void {
    [$family, $first, $second] = familyWithTwoUniqueAttributes();

    $importer = uniqueValuesImporter();

    $original = uniqueValuesRow($family, [$first->code => 'first-a', $second->code => 'second-a']);
    $duplicateOfFirst = uniqueValuesRow($family, [$first->code => 'first-a', $second->code => 'second-b']);
    $duplicateOfSecond = uniqueValuesRow($family, [$first->code => 'first-c', $second->code => 'second-a']);

    expect($importer->validateRow($original, 1))->toBeTrue()
        ->and($importer->validateRow($duplicateOfFirst, 2))->toBeFalse()
        ->and($importer->validateRow($duplicateOfSecond, 3))->toBeFalse();
});

it('rejects an in-file duplicate of a scoped unique attribute within the same scope', function (array $scope): void {
    [$family, $first] = familyWithTwoUniqueAttributes($scope);

    $importer = uniqueValuesImporter();

    expect($importer->validateRow(uniqueValuesRow($family, [$first->code => 'scoped-a']), 1))->toBeTrue()
        ->and($importer->validateRow(uniqueValuesRow($family, [$first->code => 'scoped-a']), 2))->toBeFalse();
})->with([
    'per locale'             => [['value_per_locale' => 1]],
    'per channel'            => [['value_per_channel' => 1]],
    'per channel and locale' => [['value_per_locale' => 1, 'value_per_channel' => 1]],
]);

it('accepts the same scoped unique value in a different locale', function (): void {
    [$family, $first] = familyWithTwoUniqueAttributes(['value_per_locale' => 1]);

    $otherLocale = Locale::factory()->create(['status' => 1]);
    Channel::where('code', 'default')->firstOrFail()->locales()->attach($otherLocale);

    $importer = uniqueValuesImporter();

    expect($importer->validateRow(uniqueValuesRow($family, [$first->code => 'scoped-a']), 1))->toBeTrue()
        ->and($importer->validateRow([...uniqueValuesRow($family, [$first->code => 'scoped-a']), 'locale' => $otherLocale->code], 2))->toBeTrue();
});

it('accepts the same unique value on another row of the same product', function (): void {
    [$family, $first] = familyWithTwoUniqueAttributes(['value_per_locale' => 1]);

    $importer = uniqueValuesImporter();

    $row = uniqueValuesRow($family, [$first->code => 'scoped-a']);

    expect($importer->validateRow($row, 1))->toBeTrue()
        ->and($importer->validateRow($row, 2))->toBeTrue();
});

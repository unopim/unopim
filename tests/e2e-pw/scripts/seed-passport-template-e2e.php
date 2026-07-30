<?php

/**
 * Seeds the fixture the passport-template E2E drives: an EV traction battery, the
 * first product group the EU actually requires a passport for (Regulation
 * 2023/1542, Annex XIII). Attribute codes and values mirror the data points a
 * cell manufacturer publishes, so the run exercises a real client workflow rather
 * than placeholder text.
 *
 * Idempotent — re-running returns the same ids.
 *
 * Run from the application root:
 *   php tests/e2e-pw/scripts/seed-passport-template-e2e.php
 */

require __DIR__.'/../../../vendor/autoload.php';

$app = require_once __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeGroupProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\LocaleProxy;
use Webkul\Product\Models\ProductProxy;

$familyCode = 'battery_dpp';
$groupCode = 'battery_specs';
$sku = 'BATT-NMC811-78KWH';

/**
 * Annex XIII data points, as a merchant would already hold them in the PIM: the
 * passport template maps these, it does not replace them.
 */
$attributes = [
    'battery_unique_id'                    => ['label' => 'Unique Battery Identifier', 'value' => '04012345678905'],
    'battery_manufacturer'                 => ['label' => 'Manufacturer', 'value' => 'Nordvolt Cells AB'],
    'battery_category'                     => ['label' => 'Battery Category', 'value' => 'Electric vehicle battery'],
    'battery_chemistry'                    => ['label' => 'Battery Chemistry', 'value' => 'Li-ion NMC 811'],
    'battery_manufacture_date'             => ['label' => 'Date of Manufacture', 'value' => '2026-03-18'],
    'battery_place_of_manufacture'         => ['label' => 'Place of Manufacture', 'value' => 'Skellefteå, Sweden'],
    'battery_weight'                       => ['label' => 'Battery Weight', 'value' => '452 kg'],
    'battery_rated_capacity'               => ['label' => 'Rated Capacity', 'value' => '78 kWh'],
    'battery_nominal_voltage'              => ['label' => 'Nominal Voltage', 'value' => '400 V'],
    'battery_expected_lifetime'            => ['label' => 'Expected Lifetime', 'value' => '8 years / 160 000 km'],
    'battery_cycle_life'                   => ['label' => 'Cycle Life', 'value' => '1 500 cycles at 80% depth of discharge'],
    'battery_state_of_health'              => ['label' => 'State of Health', 'value' => '100% (at placing on market)'],
    'battery_carbon_footprint'             => ['label' => 'Carbon Footprint', 'value' => '61.4 kg CO2e per kWh'],
    'battery_recycled_cobalt'              => ['label' => 'Recycled Cobalt Share', 'value' => '16%'],
    'battery_recycled_lithium'             => ['label' => 'Recycled Lithium Share', 'value' => '6%'],
    'battery_recycled_nickel'              => ['label' => 'Recycled Nickel Share', 'value' => '6%'],
    'battery_hazardous_substances'         => ['label' => 'Hazardous Substances', 'value' => 'Nickel compounds (CAS 12054-48-7), lithium hexafluorophosphate (CAS 21324-40-3)'],
    'battery_safety_measures'              => ['label' => 'Safety Measures', 'value' => 'Do not pierce or immerse. Thermal runaway risk above 60 °C.'],
    'battery_dismantling_information'      => ['label' => 'Dismantling Instructions', 'value' => 'Discharge below 30% SoC, isolate HV connector, remove 14 M8 bolts, lift module stack with insulated tooling.'],
    'battery_collection_information'       => ['label' => 'Collection and Recycling', 'value' => 'Return to any authorised treatment facility; producer take-back is free of charge.'],
    'battery_supply_chain_notes'           => ['label' => 'Supply Chain Notes', 'value' => 'Cathode active material: Tier 1 supplier, Kokkola FI. Cobalt refined in Finland, OECD due-diligence audited.'],
    'battery_conformity_declaration'       => ['label' => 'EU Declaration of Conformity', 'value' => 'DoC-NV-2026-00184 (Reg. 2023/1542, UN ECE R100.03)'],
];

$locales = LocaleProxy::modelClass()::query()->pluck('code');

$translations = fn (string $value): array => $locales
    ->mapWithKeys(fn (string $code): array => [$code => ['name' => $value]])
    ->all();

$created = [];

foreach ($attributes as $code => $definition) {
    $created[$code] = AttributeProxy::modelClass()::query()->where('code', $code)->first()
        ?? AttributeProxy::modelClass()::create(array_merge([
            'code'              => $code,
            'type'              => 'text',
            'value_per_locale'  => 0,
            'value_per_channel' => 0,
            'is_required'       => 0,
            'is_unique'         => 0,
        ], $translations($definition['label'])));
}

$group = AttributeGroupProxy::modelClass()::query()->where('code', $groupCode)->first()
    ?? AttributeGroupProxy::modelClass()::create(array_merge(['code' => $groupCode], $translations('Battery Specifications')));

$family = AttributeFamilyProxy::modelClass()::query()->where('code', $familyCode)->first();

if ($family === null) {
    $family = AttributeFamilyProxy::modelClass()::factory()
        ->withMinimalAttributesForProductTypes()
        ->create(array_merge(['code' => $familyCode], $translations('EV Battery')));
}

if (! $family->familyGroups()->where('attribute_group_id', $group->id)->exists()) {
    $family->familyGroups()->attach($group->id);
}

$mapping = $family->attributeFamilyGroupMappings()->where('attribute_group_id', $group->id)->first();

foreach ($created as $attribute) {
    if ($mapping !== null && ! $mapping->customAttributes->contains('id', $attribute->id)) {
        $mapping->customAttributes()->attach($attribute->id);
    }
}

$channel = ChannelProxy::modelClass()::query()->where('code', 'default')->firstOrFail();

$values = ['common' => ['sku' => $sku]];

foreach ($attributes as $code => $definition) {
    $values['common'][$code] = $definition['value'];
}

$product = ProductProxy::modelClass()::query()->where('sku', $sku)->first();

if ($product === null) {
    $product = ProductProxy::modelClass()::create([
        'sku'                 => $sku,
        'type'                => 'simple',
        'attribute_family_id' => $family->id,
        'status'              => 1,
        'values'              => $values,
    ]);
} else {
    $product->update(['attribute_family_id' => $family->id, 'values' => $values]);
}

foreach ([
    'catalog.product_passport.settings.enabled',
    'general.publication.settings.enabled',
] as $code) {
    CoreConfig::query()->updateOrCreate(
        ['code' => $code, 'channel_code' => $channel->code, 'locale_code' => null],
        ['value' => '1'],
    );
}

echo json_encode([
    'family_id'    => $family->id,
    'family_name'  => $family->getTranslatedValueWithFallback('name'),
    'product_id'   => $product->id,
    'sku'          => $sku,
    'channel_id'   => $channel->id,
    'channel_code' => $channel->code,
    'locale_ids'   => $channel->locales()->pluck('locales.id')->all(),
    'locale_codes' => $channel->locales()->pluck('locales.code')->all(),
    'attributes'   => collect($attributes)->map(fn (array $definition, string $code): array => [
        'code'  => $code,
        'label' => $definition['label'],
        'value' => $definition['value'],
    ])->values()->all(),
], JSON_THROW_ON_ERROR), PHP_EOL;

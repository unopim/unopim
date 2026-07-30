<?php

/**
 * Builds a demo catalogue that exercises every passport template shape at once —
 * a battery (Reg. 2023/1542), a textile (ESPR general) and a merchant-authored
 * electronics template — then publishes one product per family and reports what
 * each passport actually resolved.
 *
 * Field sources are auto-mapped by code: the demo attributes are named after the
 * template fields, which is also the shape a merchant lands on when they model
 * attributes from a preset.
 *
 * Idempotent. Run from the application root:
 *   php tests/e2e-pw/scripts/seed-passport-demo-catalog.php
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
use Webkul\ProductPassport\Database\Seeders\PassportPresetSeeder;
use Webkul\ProductPassport\Models\PassportTemplate;
use Webkul\ProductPassport\Services\PassportReadinessService;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Services\Publisher;

$locales = LocaleProxy::modelClass()::query()->pluck('code');

$translated = fn (string $value): array => $locales
    ->mapWithKeys(fn (string $code): array => [$code => ['name' => $value]])
    ->all();

$attributeFor = function (string $code, string $label, string $type = 'text') use ($translated) {
    return AttributeProxy::modelClass()::query()->where('code', $code)->first()
        ?? AttributeProxy::modelClass()::create(array_merge([
            'code'              => $code,
            'type'              => $type,
            'value_per_locale'  => 0,
            'value_per_channel' => 0,
            'is_required'       => 0,
            'is_unique'         => 0,
        ], $translated($label)));
};

$familyFor = function (string $code, string $name, array $attributes) use ($translated) {
    $family = AttributeFamilyProxy::modelClass()::query()->where('code', $code)->first()
        ?? AttributeFamilyProxy::modelClass()::factory()
            ->withMinimalAttributesForProductTypes()
            ->create(array_merge(['code' => $code], $translated($name)));

    $group = AttributeGroupProxy::modelClass()::query()->where('code', $code.'_specs')->first()
        ?? AttributeGroupProxy::modelClass()::create(array_merge(['code' => $code.'_specs'], $translated($name.' Specifications')));

    if (! $family->familyGroups()->where('attribute_group_id', $group->id)->exists()) {
        $family->familyGroups()->attach($group->id);
    }

    $mapping = $family->attributeFamilyGroupMappings()->where('attribute_group_id', $group->id)->first();

    foreach ($attributes as $attribute) {
        if ($mapping !== null && ! $mapping->customAttributes->contains('id', $attribute->id)) {
            $mapping->customAttributes()->attach($attribute->id);
        }
    }

    return $family->refresh();
};

/** Battery — the preset ships the schema, the demo supplies the Annex XIII values. */
$battery = [
    'battery_unique_id'               => ['Unique Battery Identifier', '04012345678905'],
    'battery_manufacturer'            => ['Manufacturer', 'Nordvolt Cells AB'],
    'battery_category'                => ['Battery Category', 'Electric vehicle battery'],
    'battery_manufacture_date'        => ['Date of Manufacture', '2026-03-18'],
    'battery_place_of_manufacture'    => ['Place of Manufacture', 'Skellefteå, Sweden'],
    'battery_rated_capacity'          => ['Rated Capacity', '78 kWh'],
    'battery_nominal_voltage'         => ['Nominal Voltage', '400 V'],
    'battery_weight'                  => ['Battery Weight', '452 kg'],
    'battery_chemistry'               => ['Battery Chemistry', 'Li-ion NMC 811'],
    'battery_hazardous_substances'    => ['Hazardous Substances', 'Nickel compounds (CAS 12054-48-7)'],
    'battery_carbon_footprint'        => ['Carbon Footprint', '61.4 kg CO2e per kWh'],
    'battery_safety_measures'         => ['Safety Measures', 'Do not pierce or immerse.'],
    'battery_collection_information'  => ['Collection and Recycling', 'Free producer take-back at any authorised facility.'],
    'battery_dismantling_information' => ['Dismantling Information', 'Discharge below 30% SoC, isolate HV connector.'],
    'battery_conformity_declaration'  => ['EU Declaration of Conformity', 'DoC-NV-2026-00184'],
];

/** Textile — ESPR general preset field codes. */
$textile = [
    'dpp_gtin'                     => ['GTIN', '07612345678900'],
    'dpp_material_composition'     => ['Material Composition', 'Organic cotton 72%, recycled polyester 28%'],
    'dpp_end_of_life_instructions' => ['End of Life Instructions', 'Return to in-store textile collection.'],
    'dpp_manufacturer_name'        => ['Manufacturer Name', 'Lisbon Weavers Lda'],
    'dpp_country_of_origin'        => ['Country of Origin', 'Portugal'],
    'dpp_care_instructions'        => ['Care Instructions', 'Wash at 30 °C, do not bleach.'],
    'dpp_supply_chain_notes'       => ['Supply Chain Notes', 'Spinning and dyeing audited, Guimarães PT.'],
];

/** Electronics — a merchant-authored template, not a shipped preset. */
$electronics = [
    'ee_model'              => ['Model Identifier', 'NB-14-ULTRA-2026'],
    'ee_repairability'      => ['Repairability Score', '8.1 / 10'],
    'ee_spare_parts'        => ['Spare Parts Availability', '10 years from last production date'],
    'ee_energy_class'       => ['Energy Class', 'B'],
    'ee_recycled_content'   => ['Recycled Content', 'Enclosure 65% post-consumer aluminium'],
    'ee_service_manual'     => ['Service Manual Reference', 'SM-NB14U-2026-EN'],
];

$make = function (array $definitions) use ($attributeFor): array {
    $created = [];

    foreach ($definitions as $code => [$label, $value]) {
        $created[$code] = $attributeFor($code, $label);
    }

    return $created;
};

$batteryAttributes = $make($battery);
$textileAttributes = $make($textile);
$electronicsAttributes = $make($electronics);

$batteryFamily = $familyFor('demo_battery', 'Demo EV Battery', $batteryAttributes);
$textileFamily = $familyFor('demo_textile', 'Demo Textile', $textileAttributes);
$electronicsFamily = $familyFor('demo_electronics', 'Demo Electronics', $electronicsAttributes);

$seeder = resolve(PassportPresetSeeder::class);

$seeder->run('battery');
$seeder->run('espr_general');

/** The third template stands in for a merchant who builds their own from scratch. */
$electronicsTemplate = PassportTemplate::query()->where('code', 'demo_electronics_dpp')->first();

if ($electronicsTemplate === null) {
    $electronicsTemplate = PassportTemplate::create(array_merge(
        ['code' => 'demo_electronics_dpp', 'is_enabled' => true],
        $locales->mapWithKeys(fn (string $code): array => [$code => ['name' => 'Consumer Electronics Passport']])->all(),
    ));

    $section = $electronicsTemplate->sections()->create(array_merge(
        ['code' => 'product', 'position' => 0],
        $locales->mapWithKeys(fn (string $code): array => [$code => ['name' => 'Product and Repair']])->all(),
    ));

    $position = 0;

    foreach ($electronics as $code => [$label]) {
        $electronicsTemplate->fields()->create(array_merge([
            'code'                         => $code,
            'passport_template_section_id' => $section->id,
            'source_type'                  => 'attribute',
            'tier'                         => $code === 'ee_service_manual' ? 'operator' : 'consumer',
            'is_required'                  => in_array($code, ['ee_model', 'ee_repairability'], true),
            'role'                         => $code === 'ee_model' ? 'model' : null,
            'position'                     => $position++,
        ], $locales->mapWithKeys(fn (string $locale): array => [$locale => ['label' => $label]])->all()));
    }
}

$bind = function (string $templateCode, $family, array $attributes): PassportTemplate {
    $template = PassportTemplate::query()->where('code', $templateCode)->firstOrFail();

    $template->families()->syncWithoutDetaching([$family->id]);

    foreach ($template->fields as $field) {
        $attribute = $attributes[$field->code] ?? null;

        if ($attribute !== null && $field->attribute_id !== $attribute->id) {
            $field->update(['attribute_id' => $attribute->id]);
        }
    }

    return $template->refresh();
};

$batteryTemplate = $bind('battery', $batteryFamily, $batteryAttributes);
$textileTemplate = $bind('espr_general', $textileFamily, $textileAttributes);
$electronicsTemplate = $bind('demo_electronics_dpp', $electronicsFamily, $electronicsAttributes);

$channel = ChannelProxy::modelClass()::query()->where('code', 'default')->firstOrFail();
$locale = $channel->locales()->firstOrFail();

foreach (['catalog.product_passport.settings.enabled', 'general.publication.settings.enabled'] as $code) {
    CoreConfig::query()->updateOrCreate(
        ['code' => $code, 'channel_code' => $channel->code, 'locale_code' => null],
        ['value' => '1'],
    );
}

$productFor = function (string $sku, $family, array $definitions, array $attributes, $locale) {
    $values = ['common' => ['sku' => $sku]];

    /**
     * A locale-scoped attribute keeps its value in `locale_specific`; the resolver
     * reads the bucket the attribute declares, not wherever the value was written.
     */
    foreach ($definitions as $code => [, $value]) {
        $attribute = $attributes[$code] ?? null;

        if ($attribute !== null && $attribute->value_per_locale) {
            $values['locale_specific'][$locale->code][$code] = $value;

            continue;
        }

        $values['common'][$code] = $value;
    }

    $product = ProductProxy::modelClass()::query()->where('sku', $sku)->first()
        ?? ProductProxy::modelClass()::create([
            'sku'                 => $sku,
            'type'                => 'simple',
            'attribute_family_id' => $family->id,
            'status'              => 1,
        ]);

    // `values` is guarded on the product model, so it is assigned rather than filled.
    $product->attribute_family_id = $family->id;
    $product->values = $values;
    $product->save();

    return $product->refresh();
};

$products = [
    'battery'     => $productFor('DEMO-BATT-78KWH', $batteryFamily, $battery, $batteryAttributes, $locale),
    'textile'     => $productFor('DEMO-TEX-HOODIE', $textileFamily, $textile, $textileAttributes, $locale),
    'electronics' => $productFor('DEMO-NB14-ULTRA', $electronicsFamily, $electronics, $electronicsAttributes, $locale),
];

$readiness = resolve(PassportReadinessService::class);
$publisher = resolve(Publisher::class);

$report = [];

foreach ($products as $group => $product) {
    $missing = $readiness->missingLabels($product, $channel, $locale);

    if ($missing === []) {
        $publisher->publish($product, $channel, $locale, 'dpp');
    }

    /** A re-publish with an unchanged checksum mints no version, so read the live one. */
    $publication = PublicationProxy::modelClass()::query()
        ->where('product_id', $product->id)
        ->where('channel_id', $channel->id)
        ->where('type', 'dpp')
        ->first();

    $version = $publication?->currentVersion($locale->id);

    $payload = $version === null ? [] : ($version->load('payloadRecord')->payload ?? []);

    $report[] = [
        'group'      => $group,
        'sku'        => $product->sku,
        'family'     => $product->attribute_family_id,
        'missing'    => $missing,
        'published'  => $version !== null,
        'public_url' => $payload['meta']['url'] ?? null,
        'uuid'       => $payload['meta']['uuid'] ?? null,
        'template'   => $payload['meta']['template'] ?? null,
        'identifier' => array_filter($payload['identifier'] ?? []),
        'sections'   => array_map(
            fn (array $section): string => $section['label'].' ('.count($section['fields']).')',
            $payload['sections'] ?? [],
        ),
        'tiers' => array_map(
            fn (array $tier): int => count($tier['fields']),
            $payload['tiers'] ?? [],
        ),
    ];
}

echo json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), PHP_EOL;

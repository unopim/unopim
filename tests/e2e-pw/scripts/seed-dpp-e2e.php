<?php

// Idempotent backend prereq for the DPP E2E suites. Run once per env:
//   docker exec unopim-unopim-fpm-1 php artisan tinker tests/e2e-pw/scripts/seed-dpp-e2e.php
// Builds the `dpp` attribute group with the canonical dpp_* attributes (the
// retired DppAttributeSeeder's set, now test-only data), a lean `dpp_e2e`
// attribute family, a `dpp_e2e` passport template bound to that family whose
// fields mirror the old tier map (so publishing is gated and tiered exactly as
// the specs assume), a second channel locale (fr_FR), and the passport /
// publication config the specs expect.

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Locale;
use Webkul\ProductPassport\Enums\PassportFieldSource;
use Webkul\ProductPassport\Models\PassportTemplateProxy;

/* --- the dpp attribute set the specs fill (formerly DppAttributeSeeder) --- */
$dppAttributes = [
    ['code' => 'dpp_material_composition', 'type' => 'textarea', 'locale' => true, 'channel' => false],
    ['code' => 'dpp_substances_of_concern', 'type' => 'textarea', 'locale' => true, 'channel' => false],
    ['code' => 'dpp_recycled_content_pct', 'type' => 'text', 'locale' => false, 'channel' => false],
    ['code' => 'dpp_carbon_footprint', 'type' => 'text', 'locale' => false, 'channel' => false],
    ['code' => 'dpp_energy_consumption', 'type' => 'text', 'locale' => false, 'channel' => false],
    ['code' => 'dpp_durability_statement', 'type' => 'textarea', 'locale' => true, 'channel' => false],
    ['code' => 'dpp_repairability_score', 'type' => 'text', 'locale' => false, 'channel' => false],
    ['code' => 'dpp_spare_parts_availability', 'type' => 'textarea', 'locale' => true, 'channel' => false],
    ['code' => 'dpp_care_instructions', 'type' => 'textarea', 'locale' => true, 'channel' => false],
    ['code' => 'dpp_disassembly_guide', 'type' => 'file', 'locale' => false, 'channel' => false, 'extensions' => ['pdf']],
    ['code' => 'dpp_manufacturer_name', 'type' => 'text', 'locale' => false, 'channel' => false],
    ['code' => 'dpp_manufacturing_site', 'type' => 'text', 'locale' => true, 'channel' => false],
    ['code' => 'dpp_country_of_origin', 'type' => 'text', 'locale' => false, 'channel' => false],
    ['code' => 'dpp_supply_chain_notes', 'type' => 'textarea', 'locale' => true, 'channel' => false],
    ['code' => 'dpp_end_of_life_instructions', 'type' => 'textarea', 'locale' => true, 'channel' => false],
    ['code' => 'dpp_take_back_scheme', 'type' => 'textarea', 'locale' => true, 'channel' => true],
    ['code' => 'dpp_declaration_of_conformity', 'type' => 'file', 'locale' => false, 'channel' => false, 'extensions' => ['pdf']],
    ['code' => 'dpp_test_reports', 'type' => 'file', 'locale' => false, 'channel' => false, 'extensions' => ['pdf']],
    ['code' => 'dpp_certificates', 'type' => 'file', 'locale' => false, 'channel' => false, 'extensions' => ['pdf']],
    ['code' => 'dpp_gtin', 'type' => 'text', 'locale' => false, 'channel' => false],
    ['code' => 'dpp_model_identifier', 'type' => 'text', 'locale' => false, 'channel' => false],
    ['code' => 'dpp_batch_identifier', 'type' => 'text', 'locale' => false, 'channel' => false],
    ['code' => 'dpp_warranty_terms', 'type' => 'textarea', 'locale' => true, 'channel' => true],
];

$labelFor = fn (string $code): string => ucwords(str_replace('_', ' ', preg_replace('/^dpp_/', '', $code)));

$dppGroup = AttributeGroup::firstOrCreate(['code' => 'dpp']);
DB::table('attribute_group_translations')->updateOrInsert(
    ['attribute_group_id' => $dppGroup->id, 'locale' => 'en_US'],
    ['name' => 'Digital Product Passport'],
);

foreach ($dppAttributes as $definition) {
    $attribute = Attribute::firstOrCreate(
        ['code' => $definition['code']],
        [
            'type'               => $definition['type'],
            'value_per_locale'   => $definition['locale'] ? 1 : 0,
            'value_per_channel'  => $definition['channel'] ? 1 : 0,
            'is_required'        => 0,
            'is_unique'          => 0,
            'allowed_extensions' => $definition['extensions'] ?? null,
        ],
    );

    DB::table('attribute_translations')->updateOrInsert(
        ['attribute_id' => $attribute->id, 'locale' => 'en_US'],
        ['name' => $labelFor($definition['code'])],
    );
}

/* --- plain source attributes for extra-source scenarios --- */
$sources = [
    'origin_country' => ['en_US' => 'Origin Country', 'fr_FR' => "Pays d'origine"],
    'shelf_life'     => ['en_US' => 'Shelf Life', 'fr_FR' => 'Durée de conservation'],
];

foreach ($sources as $code => $names) {
    $attribute = Attribute::firstOrCreate(
        ['code' => $code],
        ['type' => 'text', 'value_per_locale' => 0, 'value_per_channel' => 0, 'is_required' => 0, 'is_unique' => 0],
    );

    foreach ($names as $locale => $name) {
        DB::table('attribute_translations')->updateOrInsert(
            ['attribute_id' => $attribute->id, 'locale' => $locale],
            ['name' => $name],
        );
    }
}

/* --- canonical LEAN family: only sku/status are required, plus the full dpp group + sources ---
 * Cloning `default` would drag in required name/url_key/description/price attributes and make every
 * scenario fill a WYSIWYG + price grid before it can save. A minimal family keeps sku the only
 * mandatory field (auto-filled by the create modal), so scenarios post just their dpp values. */
$family = AttributeFamily::firstOrCreate(['code' => 'dpp_e2e'], ['status' => 1]);

DB::table('attribute_family_translations')->updateOrInsert(
    ['attribute_family_id' => $family->id, 'locale' => 'en_US'],
    ['name' => 'DPP E2E'],
);

// Reset the family's group structure so a re-run always yields the lean shape regardless of prior state.
foreach ($family->attributeFamilyGroupMappings as $mapping) {
    $mapping->customAttributes()->detach();
}
$family->familyGroups()->detach();
$family->load('attributeFamilyGroupMappings');

$generalGroup = AttributeGroup::firstOrCreate(['code' => 'dpp_e2e_general']);
DB::table('attribute_group_translations')->updateOrInsert(
    ['attribute_group_id' => $generalGroup->id, 'locale' => 'en_US'],
    ['name' => 'General'],
);

$family->familyGroups()->syncWithoutDetaching([$generalGroup->id, $dppGroup->id]);
$family->load('attributeFamilyGroupMappings');

$generalMapping = $family->attributeFamilyGroupMappings()->where('attribute_group_id', $generalGroup->id)->first();
$generalMapping->customAttributes()->syncWithoutDetaching(
    Attribute::whereIn('code', ['sku', 'status', 'origin_country', 'shelf_life'])->pluck('id')->all(),
);

$dppMapping = $family->attributeFamilyGroupMappings()->where('attribute_group_id', $dppGroup->id)->first();
foreach (Attribute::where('code', 'like', 'dpp_%')->pluck('id') as $id) {
    $dppMapping->customAttributes()->syncWithoutDetaching([$id]);
}

/* --- passport template bound to the family: publishing is gated on a template
 * existing, and tiers/roles are now declared per template field. The tier map
 * mirrors the retired config-level map the suites were written against; no
 * field is required, so a product is always publishable (the old suites relied
 * on an always-satisfied completeness gate the same way). --- */
$tierFor = fn (string $code): string => match ($code) {
    'dpp_supply_chain_notes', 'dpp_manufacturing_site'                      => 'operator',
    'dpp_declaration_of_conformity', 'dpp_test_reports', 'dpp_certificates' => 'authority',
    default                                                                 => 'consumer',
};

$roleFor = fn (string $code): ?string => match ($code) {
    'dpp_gtin'             => 'gtin',
    'dpp_model_identifier' => 'model',
    'dpp_batch_identifier' => 'batch',
    default                => null,
};

$template = PassportTemplateProxy::modelClass()::firstOrCreate(
    ['code' => 'dpp_e2e'],
    ['is_enabled' => true, 'en_US' => ['name' => 'DPP E2E']],
);

$template->families()->syncWithoutDetaching([$family->id]);

if ($template->fields()->count() === 0) {
    $section = $template->sections()->create([
        'code'     => 'dpp_e2e_main',
        'position' => 0,
        'en_US'    => ['name' => 'Product Data'],
    ]);

    foreach ($dppAttributes as $position => $definition) {
        $template->fields()->create([
            'code'                         => $definition['code'],
            'passport_template_section_id' => $section->id,
            'source_type'                  => PassportFieldSource::Attribute,
            'attribute_id'                 => Attribute::where('code', $definition['code'])->value('id'),
            'tier'                         => $tierFor($definition['code']),
            'is_required'                  => false,
            'role'                         => $roleFor($definition['code']),
            'position'                     => $position,
            'en_US'                        => ['label' => $labelFor($definition['code'])],
        ]);
    }
}

/* --- second locale on the default channel for multi-locale scenarios --- */
$channel = Channel::where('code', 'default')->firstOrFail();

$fr = Locale::where('code', 'fr_FR')->firstOrFail();
$fr->update(['status' => 1]);
$channel->locales()->syncWithoutDetaching([$fr->id]);

/* --- deterministic catalog scope: specs type into en_US[...] fields, but a
       fresh admin has no catalog locale and the scope then falls back to the
       channel's alphabetically-first locale (de_DE with demo data) --- */
$enUs = Locale::where('code', 'en_US')->firstOrFail();
$enUs->update(['status' => 1]);
$channel->locales()->syncWithoutDetaching([$enUs->id]);

DB::table('admins')->whereNull('catalog_locale_id')->update(['catalog_locale_id' => $enUs->id]);

/* --- passport / publication config the specs assume --- */
$config = [
    'catalog.product_passport.settings.enabled'       => '1',
    'catalog.product_passport.settings.auto_publish'  => '0',
    'catalog.product_passport.settings.operator_name' => 'Acme Corp GmbH',
    'general.publication.settings.enabled'            => '1',
    'general.publication.settings.indexable'          => '0',
];

foreach ($config as $code => $value) {
    CoreConfig::updateOrCreate(
        ['code' => $code, 'channel_code' => null, 'locale_code' => null],
        ['value' => $value],
    );
}

echo 'SEED OK family_id='.$family->id
    .' template_id='.$template->id
    .' dpp_attrs='.Attribute::where('code', 'like', 'dpp_%')->count()
    .' template_fields='.$template->fields()->count()
    .' locales='.$channel->locales()->count()
    ."\n";

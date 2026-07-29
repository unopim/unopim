<?php

// Idempotent backend prereq for the DPP E2E suites. Run once per env:
//   docker exec unopim-unopim-fpm-1 php artisan tinker tests/e2e-pw/scripts/seed-dpp-e2e.php
// Builds a canonical `dpp_e2e` attribute family (cloned from `default` + the full
// `dpp` group + two plain source attributes), a completeness requirement that is
// always satisfied (sku), a second channel locale (fr_FR) for multi-locale
// scenarios, and the passport/publication config the specs assume.

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Locale;
use Webkul\ProductPassport\Database\Seeders\DppAttributeSeeder;

resolve(DppAttributeSeeder::class)->run();

/* --- plain source attributes for field-mapping + custom-field scenarios --- */
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

$dppGroup = AttributeGroup::where('code', 'dpp')->firstOrFail();

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

/* --- completeness requirement = sku (always filled -> 100%) on default channel --- */
$channel = Channel::where('code', 'default')->firstOrFail();

DB::table('completeness_settings')->updateOrInsert(
    ['family_id' => $family->id, 'channel_id' => $channel->id, 'attribute_id' => 1],
    ['updated_at' => now(), 'created_at' => now()],
);

/* --- second locale on the default channel for multi-locale scenarios --- */
$fr = Locale::where('code', 'fr_FR')->firstOrFail();
$fr->update(['status' => 1]);
$channel->locales()->syncWithoutDetaching([$fr->id]);

/* --- passport / publication config the specs assume --- */
$config = [
    'catalog.product_passport.settings.enabled'                => '1',
    'catalog.product_passport.settings.auto_publish'           => '0',
    'catalog.product_passport.settings.completeness_threshold' => '1',
    'catalog.product_passport.settings.operator_name'          => 'Acme Corp GmbH',
    'general.publication.settings.enabled'                     => '1',
    'general.publication.settings.indexable'                   => '0',
];

foreach ($config as $code => $value) {
    CoreConfig::updateOrCreate(
        ['code' => $code, 'channel_code' => null, 'locale_code' => null],
        ['value' => $value],
    );
}

echo 'SEED OK family_id='.$family->id
    .' dpp_attrs='.Attribute::where('code', 'like', 'dpp_%')->count()
    .' locales='.$channel->locales()->count()
    ."\n";

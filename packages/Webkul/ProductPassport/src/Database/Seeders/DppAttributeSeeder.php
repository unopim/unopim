<?php

namespace Webkul\ProductPassport\Database\Seeders;

use Webkul\Attribute\Models\Attribute as AttributeModel;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Models\LocaleProxy;

/**
 * Seeds the `dpp` attribute group and its attributes, idempotently. The only
 * call site is `unopim:passport:install-attributes` — never a migration or a
 * service provider boot hook, so installs opt in explicitly.
 */
class DppAttributeSeeder
{
    private const GROUP_CODE = 'dpp';

    /**
     * @var list<array{code: string, type: string, locale: bool, channel: bool, extensions?: list<string>}>
     */
    private const ATTRIBUTES = [
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

    public function __construct(
        private readonly AttributeGroupRepository $groups,
        private readonly AttributeRepository $attributes,
    ) {}

    /**
     * Creates the `dpp` group and every attribute above if missing. Safe to
     * call repeatedly — existing rows (matched by `code`) are left untouched.
     */
    public function run(): void
    {
        if (! AttributeGroup::where('code', self::GROUP_CODE)->exists()) {
            $this->groups->create(array_merge(
                ['code' => self::GROUP_CODE],
                $this->translatedNamesFor('passport::app.groups.dpp'),
            ));
        }

        foreach (self::ATTRIBUTES as $definition) {
            if (AttributeModel::where('code', $definition['code'])->exists()) {
                continue;
            }

            $this->attributes->create(array_merge([
                'code'               => $definition['code'],
                'type'               => $definition['type'],
                'value_per_locale'   => $definition['locale'] ? 1 : 0,
                'value_per_channel'  => $definition['channel'] ? 1 : 0,
                'is_required'        => 0,
                'is_unique'          => 0,
                'allowed_extensions' => $definition['extensions'] ?? null,
            ], $this->translatedNamesFor('passport::app.attributes.'.$definition['code'])));
        }
    }

    /**
     * Builds the per-locale `name` payload `TranslatableModel::fill()`
     * actually expects: each locale code as its own TOP-LEVEL key mapping to
     * `['name' => $value]` — NOT a `name` key holding a locale-keyed map (that
     * shape silently becomes a PHP array bound into a single text column and
     * fails with an "Array to string conversion" SQL error).
     *
     * @return array<string, array{name: string}>
     */
    private function translatedNamesFor(string $key): array
    {
        return LocaleProxy::modelClass()::query()
            ->pluck('code')
            ->mapWithKeys(fn (string $localeCode): array => [$localeCode => ['name' => trans($key, [], $localeCode)]])
            ->all();
    }
}

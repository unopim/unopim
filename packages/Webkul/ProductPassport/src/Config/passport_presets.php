<?php

use Webkul\ProductPassport\Enums\PassportFieldRole;
use Webkul\ProductPassport\Enums\PassportFieldTier;

/**
 * Ready-made passport templates the installer can materialize, keyed by template
 * code. A preset is a starting point, not a fixture: `PassportPresetSeeder` binds
 * it to no family and leaves every field unsourced, so nothing publishes until an
 * admin picks the families and points each field at an attribute they maintain.
 *
 * Integrators add their own product-group presets (battery, textile, construction)
 * by merging into this config — labels resolve from the `label_key` per enabled
 * catalog locale, so a preset ships translated wherever the keys exist.
 */
return [
    /**
     * Batteries are the first product group the EU actually mandates a passport
     * for (Regulation 2023/1542, Annex XIII, from 18 February 2027), so the data
     * points and their disclosure levels follow that annex rather than the
     * general ESPR list: repair and repurposing detail is operator-tier, the
     * conformity declaration is authority-tier.
     */
    'battery' => [
        'name_key' => 'passport::app.templates.preset.battery.name',

        'sections' => [
            'identity'     => 'passport::app.templates.preset.battery.sections.identity',
            'performance'  => 'passport::app.templates.preset.battery.sections.performance',
            'materials'    => 'passport::app.templates.preset.battery.sections.materials',
            'circularity'  => 'passport::app.templates.preset.battery.sections.circularity',
            'compliance'   => 'passport::app.templates.preset.battery.sections.compliance',
        ],

        'label_prefix' => 'passport::app.templates.preset.battery.fields.',

        'fields' => [
            ['code' => 'battery_unique_id', 'section' => 'identity', 'role' => PassportFieldRole::Gtin, 'required' => true],
            ['code' => 'battery_manufacturer', 'section' => 'identity', 'required' => true],
            ['code' => 'battery_category', 'section' => 'identity', 'required' => true],
            ['code' => 'battery_manufacture_date', 'section' => 'identity', 'required' => true],
            ['code' => 'battery_place_of_manufacture', 'section' => 'identity'],
            ['code' => 'battery_batch', 'section' => 'identity', 'role' => PassportFieldRole::Batch],
            ['code' => 'battery_model', 'section' => 'identity', 'role' => PassportFieldRole::Model],

            ['code' => 'battery_rated_capacity', 'section' => 'performance', 'required' => true],
            ['code' => 'battery_nominal_voltage', 'section' => 'performance', 'required' => true],
            ['code' => 'battery_weight', 'section' => 'performance', 'required' => true],
            ['code' => 'battery_expected_lifetime', 'section' => 'performance'],
            ['code' => 'battery_cycle_life', 'section' => 'performance'],
            ['code' => 'battery_state_of_health', 'section' => 'performance', 'tier' => PassportFieldTier::Operator],

            ['code' => 'battery_chemistry', 'section' => 'materials', 'required' => true],
            ['code' => 'battery_hazardous_substances', 'section' => 'materials', 'required' => true],
            ['code' => 'battery_carbon_footprint', 'section' => 'materials', 'required' => true],
            ['code' => 'battery_recycled_cobalt', 'section' => 'materials'],
            ['code' => 'battery_recycled_lithium', 'section' => 'materials'],
            ['code' => 'battery_recycled_nickel', 'section' => 'materials'],
            ['code' => 'battery_recycled_lead', 'section' => 'materials'],

            ['code' => 'battery_safety_measures', 'section' => 'circularity', 'required' => true],
            ['code' => 'battery_collection_information', 'section' => 'circularity', 'required' => true],
            ['code' => 'battery_dismantling_information', 'section' => 'circularity', 'tier' => PassportFieldTier::Operator],
            ['code' => 'battery_supply_chain_notes', 'section' => 'circularity', 'tier' => PassportFieldTier::Operator],

            ['code' => 'battery_conformity_declaration', 'section' => 'compliance', 'tier' => PassportFieldTier::Authority, 'required' => true],
            ['code' => 'battery_test_reports', 'section' => 'compliance', 'tier' => PassportFieldTier::Authority],
        ],
    ],

    'espr_general' => [
        'name_key'     => 'passport::app.templates.preset.name',
        'label_prefix' => 'passport::app.templates.preset.fields.',

        'sections' => [
            'identification' => 'passport::app.templates.preset.sections.identification',
            'materials'      => 'passport::app.templates.preset.sections.materials',
            'circularity'    => 'passport::app.templates.preset.sections.circularity',
            'footprint'      => 'passport::app.templates.preset.sections.footprint',
            'supply_chain'   => 'passport::app.templates.preset.sections.supply-chain',
            'compliance'     => 'passport::app.templates.preset.sections.compliance',
        ],

        /**
         * Tiers mirror the ESPR disclosure split: compliance evidence and
         * supply-chain detail stay behind operator/authority elevation, everything
         * else is consumer facing. Required marks what the regulation expects a
         * published passport to carry, which is what the publish gate enforces.
         */
        'fields' => [
            ['code' => 'dpp_gtin', 'section' => 'identification', 'role' => PassportFieldRole::Gtin, 'required' => true],
            ['code' => 'dpp_model_identifier', 'section' => 'identification', 'role' => PassportFieldRole::Model],
            ['code' => 'dpp_batch_identifier', 'section' => 'identification', 'role' => PassportFieldRole::Batch],

            ['code' => 'dpp_material_composition', 'section' => 'materials', 'required' => true],
            ['code' => 'dpp_substances_of_concern', 'section' => 'materials'],
            ['code' => 'dpp_recycled_content_pct', 'section' => 'materials'],

            ['code' => 'dpp_durability_statement', 'section' => 'circularity'],
            ['code' => 'dpp_repairability_score', 'section' => 'circularity'],
            ['code' => 'dpp_spare_parts_availability', 'section' => 'circularity'],
            ['code' => 'dpp_care_instructions', 'section' => 'circularity'],
            ['code' => 'dpp_disassembly_guide', 'section' => 'circularity'],
            ['code' => 'dpp_end_of_life_instructions', 'section' => 'circularity', 'required' => true],
            ['code' => 'dpp_take_back_scheme', 'section' => 'circularity'],
            ['code' => 'dpp_warranty_terms', 'section' => 'circularity'],

            ['code' => 'dpp_carbon_footprint', 'section' => 'footprint'],
            ['code' => 'dpp_energy_consumption', 'section' => 'footprint'],

            ['code' => 'dpp_manufacturer_name', 'section' => 'supply_chain', 'required' => true],
            ['code' => 'dpp_country_of_origin', 'section' => 'supply_chain', 'required' => true],
            ['code' => 'dpp_manufacturing_site', 'section' => 'supply_chain', 'tier' => PassportFieldTier::Operator],
            ['code' => 'dpp_supply_chain_notes', 'section' => 'supply_chain', 'tier' => PassportFieldTier::Operator],

            ['code' => 'dpp_declaration_of_conformity', 'section' => 'compliance', 'tier' => PassportFieldTier::Authority],
            ['code' => 'dpp_test_reports', 'section' => 'compliance', 'tier' => PassportFieldTier::Authority],
            ['code' => 'dpp_certificates', 'section' => 'compliance', 'tier' => PassportFieldTier::Authority],
        ],
    ],
];

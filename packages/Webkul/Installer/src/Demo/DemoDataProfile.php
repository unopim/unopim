<?php

namespace Webkul\Installer\Demo;

/**
 * Immutable value object describing which parts of the demo catalogue
 * should be seeded. Built from preset, interactive prompts, or CLI flags.
 *
 * Used by DatabaseSeeder and every reference catalog seeder to decide
 * which families / locales / channels to insert.
 */
class DemoDataProfile
{
    public const PRESET_MINIMAL = 'minimal';

    public const PRESET_STARTER = 'starter';

    public const PRESET_MEDIUM = 'medium';

    public const PRESET_FULL = 'full';

    public const PRESET_CUSTOM = 'custom';

    /**
     * The 20 families planned across the project, grouped by the 8
     * unopim.com industries. See Database/Data/PLAN.md for the full
     * delivery roadmap.
     *
     * @var array<string, string[]>
     */
    public const INDUSTRIES_TO_FAMILIES = [
        'food'          => ['food_grocery', 'food_beverages', 'food_fresh_produce'],
        'cpg'           => ['cpg_household', 'cpg_personal_care'],
        'fashion'       => ['fashion_apparel', 'fashion_footwear', 'fashion_accessories'],
        'pharmacy'      => ['pharma_otc', 'medical_devices', 'pharma_supplements'],
        'manufacturing' => ['manufacturing_industrial', 'manufacturing_tools_mro', 'manufacturing_safety_ppe'],
        'engineering'   => ['engineering_components', 'engineering_lab_equipment'],
        'energy'        => ['energy_utility', 'energy_lighting'],
        'retail'        => ['electronics_consumer', 'building_materials'],
    ];

    /**
     * @param  string[]  $families  Family codes to seed
     * @param  string[]  $locales  Locale codes to populate
     * @param  string[]  $channels  Channel codes to populate
     */
    public function __construct(
        public readonly string $preset,
        public readonly array $families,
        public readonly array $locales,
        public readonly array $channels,
    ) {}

    public static function fromPreset(string $preset, array $userLocales = []): self
    {
        return match ($preset) {
            self::PRESET_MINIMAL => new self(
                preset: self::PRESET_MINIMAL,
                families: [],
                locales: $userLocales ?: ['en_US'],
                channels: ['default'],
            ),

            self::PRESET_STARTER => new self(
                preset: self::PRESET_STARTER,
                families: ['food_grocery'],
                locales: array_unique(array_merge(
                    ['en_US', 'en_GB', 'de_DE', 'fr_FR', 'es_ES', 'it_IT', 'nl_NL', 'pl_PL'],
                    $userLocales
                )),
                channels: ['default', 'ecommerce', 'mobile_app', 'print_catalogue', 'b2b_wholesale'],
            ),

            self::PRESET_MEDIUM => new self(
                preset: self::PRESET_MEDIUM,
                families: [
                    'food_grocery', 'food_beverages',
                    'fashion_apparel', 'fashion_footwear',
                    'pharma_otc', 'medical_devices',
                    'manufacturing_industrial',
                    'electronics_consumer',
                ],
                locales: array_unique(array_merge(
                    ['en_US', 'en_GB', 'de_DE', 'fr_FR', 'es_ES', 'it_IT', 'nl_NL', 'pl_PL'],
                    $userLocales
                )),
                channels: ['default', 'ecommerce', 'mobile_app', 'print_catalogue', 'b2b_wholesale'],
            ),

            self::PRESET_FULL => new self(
                preset: self::PRESET_FULL,
                families: array_merge(...array_values(self::INDUSTRIES_TO_FAMILIES)),
                locales: array_unique(array_merge(
                    ['en_US', 'en_GB', 'de_DE', 'fr_FR', 'es_ES', 'it_IT', 'nl_NL', 'pl_PL'],
                    $userLocales
                )),
                channels: ['ecommerce', 'mobile_app', 'print_catalogue', 'b2b_wholesale'],
            ),

            default => throw new \InvalidArgumentException(
                "Unknown demo preset: {$preset}. Expected one of: minimal, starter, medium, full, custom."
            ),
        };
    }

    public static function custom(array $families, array $locales, array $channels): self
    {
        return new self(
            preset: self::PRESET_CUSTOM,
            families: $families,
            locales: $locales ?: ['en_US'],
            channels: $channels ?: ['ecommerce'],
        );
    }

    public function isMinimal(): bool
    {
        return $this->preset === self::PRESET_MINIMAL || empty($this->families);
    }

    public function shouldSeedFamily(string $familyCode): bool
    {
        return in_array($familyCode, $this->families, true);
    }

    public function shouldSeedLocale(string $localeCode): bool
    {
        return in_array($localeCode, $this->locales, true);
    }

    public function shouldSeedChannel(string $channelCode): bool
    {
        return in_array($channelCode, $this->channels, true);
    }
}

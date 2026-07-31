<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Database\Seeders\PassportPresetSeeder;
use Webkul\Publication\Services\Publisher;

/**
 * Installs the shipped passport presets, maps them to the demo families and
 * publishes a handful of digital product passports.
 *
 * Publication goes through {@see Publisher} so payloads, checksums and version
 * history are produced by the real service rather than fabricated rows. A
 * failure here leaves the catalog intact — passports are a showcase, not a
 * prerequisite.
 */
class DemoPassportSeeder extends Seeder
{
    /**
     * Families the demo template is assigned to. A family maps to exactly one
     * template, so the shipped presets stay installed but unassigned — they are
     * there to be explored, while this template is the one that publishes.
     */
    protected const TEMPLATE_CODE = 'demo_product_passport';

    protected const TEMPLATE_FAMILIES = [
        'audio_electronics', 'apparel', 'home_kitchen', 'outdoor', 'furniture_lighting', 'sports_fitness',
    ];

    /**
     * Sections of the demo template, and the fields inside each one. Fields
     * are attribute-sourced unless a `fixed` value is given, which is how the
     * template builder models a value that is the same for every product.
     */
    protected const TEMPLATE = [
        'identification' => [
            ['code' => 'gtin', 'attribute' => 'ean', 'role' => 'gtin', 'required' => true],
            ['code' => 'model_name', 'attribute' => 'name', 'role' => 'model', 'required' => true],
            ['code' => 'model_number', 'attribute' => 'product_number', 'required' => true],
            ['code' => 'manufacturer', 'attribute' => 'brand', 'required' => true],
            ['code' => 'place_of_manufacture', 'attribute' => 'country_of_origin'],
        ],
        'materials' => [
            ['code' => 'primary_material', 'attribute' => 'material', 'required' => true],
            ['code' => 'recycled_content', 'attribute' => 'recycled_content_percent'],
            ['code' => 'product_weight', 'attribute' => 'weight'],
        ],
        'circularity' => [
            ['code' => 'warranty_period', 'attribute' => 'warranty_months'],
            ['code' => 'care_and_repair', 'attribute' => 'care_instructions'],
            ['code' => 'spare_parts_policy', 'fixed' => [
                'en_US' => 'Wear parts are stocked for ten years from the last production date and listed under Spare parts on this product.',
                'de_DE' => 'Verschleißteile werden zehn Jahre ab dem letzten Produktionsdatum bevorratet und sind bei diesem Produkt unter „Ersatzteile“ gelistet.',
                'fr_FR' => 'Les pièces d’usure sont tenues en stock pendant dix ans après la dernière date de production et figurent sous « Pièces détachées » sur ce produit.',
            ]],
        ],
        'compliance' => [
            ['code' => 'certifications', 'attribute' => 'certifications', 'required' => true],
            ['code' => 'conformity_statement', 'tier' => 'authority', 'fixed' => [
                'en_US' => 'This product complies with the applicable EU directives. The declaration of conformity is available from the manufacturer on request.',
                'de_DE' => 'Dieses Produkt entspricht den geltenden EU-Richtlinien. Die Konformitätserklärung ist beim Hersteller auf Anfrage erhältlich.',
                'fr_FR' => 'Ce produit est conforme aux directives européennes applicables. La déclaration de conformité est disponible auprès du fabricant sur demande.',
            ]],
        ],
    ];

    /**
     * Section and field labels, per locale.
     */
    protected const LABELS = [
        'en_US' => [
            'template'             => 'Demo product passport',
            'identification'       => 'Identification',
            'materials'            => 'Materials',
            'circularity'          => 'Circularity',
            'compliance'           => 'Compliance',
            'gtin'                 => 'GTIN',
            'model_name'           => 'Model name',
            'model_number'         => 'Model number',
            'manufacturer'         => 'Manufacturer',
            'place_of_manufacture' => 'Place of manufacture',
            'primary_material'     => 'Primary material',
            'recycled_content'     => 'Recycled content (%)',
            'product_weight'       => 'Product weight',
            'warranty_period'      => 'Warranty period (months)',
            'care_and_repair'      => 'Care and repair',
            'spare_parts_policy'   => 'Spare parts availability',
            'certifications'       => 'Certifications',
            'conformity_statement' => 'Declaration of conformity',
        ],
        'de_DE' => [
            'template'             => 'Demo-Produktpass',
            'identification'       => 'Identifikation',
            'materials'            => 'Materialien',
            'circularity'          => 'Kreislauffähigkeit',
            'compliance'           => 'Konformität',
            'gtin'                 => 'GTIN',
            'model_name'           => 'Modellname',
            'model_number'         => 'Modellnummer',
            'manufacturer'         => 'Hersteller',
            'place_of_manufacture' => 'Herstellungsort',
            'primary_material'     => 'Hauptmaterial',
            'recycled_content'     => 'Recyclinganteil (%)',
            'product_weight'       => 'Produktgewicht',
            'warranty_period'      => 'Garantiedauer (Monate)',
            'care_and_repair'      => 'Pflege und Reparatur',
            'spare_parts_policy'   => 'Verfügbarkeit von Ersatzteilen',
            'certifications'       => 'Zertifizierungen',
            'conformity_statement' => 'Konformitätserklärung',
        ],
        'fr_FR' => [
            'template'             => 'Passeport produit de démonstration',
            'identification'       => 'Identification',
            'materials'            => 'Matériaux',
            'circularity'          => 'Circularité',
            'compliance'           => 'Conformité',
            'gtin'                 => 'GTIN',
            'model_name'           => 'Nom du modèle',
            'model_number'         => 'Référence du modèle',
            'manufacturer'         => 'Fabricant',
            'place_of_manufacture' => 'Lieu de fabrication',
            'primary_material'     => 'Matériau principal',
            'recycled_content'     => 'Matière recyclée (%)',
            'product_weight'       => 'Poids du produit',
            'warranty_period'      => 'Durée de garantie (mois)',
            'care_and_repair'      => 'Entretien et réparation',
            'spare_parts_policy'   => 'Disponibilité des pièces détachées',
            'certifications'       => 'Certifications',
            'conformity_statement' => 'Déclaration de conformité',
        ],
    ];

    /**
     * Products published live, by channel.
     */
    protected const PUBLISHED = [
        'ecommerce' => [
            'aurex-halo-over-ear',
            'verano-atlas-rain-jacket',
            'casaluna-terra-frying-pan',
            'nordvale-fjell-28-daypack',
        ],
    ];

    public function __construct(
        protected PassportPresetSeeder $presets,
        protected Publisher $publisher,
    ) {}

    public function run(): void
    {
        $this->installPresets();

        $templateId = $this->seedTemplate();

        if ($templateId === null) {
            return;
        }

        $this->mapFamilies($templateId);

        $this->enablePassports();

        $this->publish();
    }

    /**
     * Install the shipped presets so an evaluator can see them in the template
     * list. They stay unassigned: their fields ship unmapped by design, and an
     * unmapped required field blocks publishing.
     */
    protected function installPresets(): void
    {
        foreach ($this->presets->available() as $code) {
            try {
                $this->presets->run((string) $code);
            } catch (Throwable) {
                continue;
            }
        }
    }

    /**
     * Build the demo template, mapping every field to an attribute the demo
     * catalog actually populates so the readiness gate passes.
     */
    protected function seedTemplate(): ?int
    {
        $now = Date::now();

        $attributeIds = DB::table('attributes')->pluck('id', 'code');

        DB::table('passport_templates')->updateOrInsert(
            ['code' => self::TEMPLATE_CODE],
            ['is_enabled' => true, 'updated_at' => $now, 'created_at' => $now],
        );

        $templateId = (int) DB::table('passport_templates')->where('code', self::TEMPLATE_CODE)->value('id');

        if ($templateId === 0) {
            return null;
        }

        foreach (self::LABELS as $locale => $labels) {
            DB::table('passport_template_translations')->updateOrInsert(
                ['passport_template_id' => $templateId, 'locale' => $locale],
                ['name' => $labels['template']],
            );
        }

        $sectionIds = DB::table('passport_template_sections')
            ->where('passport_template_id', $templateId)
            ->pluck('id');

        DB::table('passport_template_fields')->whereIn('passport_template_section_id', $sectionIds)->delete();
        DB::table('passport_template_sections')->where('passport_template_id', $templateId)->delete();

        $sectionPosition = 1;
        $fieldPosition = 1;

        foreach (self::TEMPLATE as $sectionCode => $fields) {
            $sectionId = DB::table('passport_template_sections')->insertGetId([
                'passport_template_id' => $templateId,
                'code'                 => $sectionCode,
                'position'             => $sectionPosition++,
            ]);

            foreach (self::LABELS as $locale => $labels) {
                DB::table('passport_template_section_translations')->insert([
                    'passport_template_section_id' => $sectionId,
                    'locale'                       => $locale,
                    'name'                         => $labels[$sectionCode],
                ]);
            }

            foreach ($fields as $field) {
                $this->seedField($templateId, $sectionId, $field, $attributeIds, $fieldPosition++);
            }
        }

        return $templateId;
    }

    /**
     * @param  array<string, mixed>  $field
     * @param  Collection<string, int>  $attributeIds
     */
    protected function seedField(int $templateId, int $sectionId, array $field, $attributeIds, int $position): void
    {
        $isFixed = isset($field['fixed']);

        $attributeId = $isFixed ? null : ($attributeIds[$field['attribute']] ?? null);

        if (! $isFixed && $attributeId === null) {
            return;
        }

        $fieldId = DB::table('passport_template_fields')->insertGetId([
            'passport_template_id'         => $templateId,
            'passport_template_section_id' => $sectionId,
            'code'                         => $field['code'],
            'source_type'                  => $isFixed ? 'fixed' : 'attribute',
            'attribute_id'                 => $attributeId === null ? null : (int) $attributeId,
            'tier'                         => $field['tier'] ?? 'consumer',
            'is_required'                  => $field['required'] ?? false,
            'role'                         => $field['role'] ?? null,
            'position'                     => $position,
        ]);

        foreach (self::LABELS as $locale => $labels) {
            DB::table('passport_template_field_translations')->insert([
                'passport_template_field_id' => $fieldId,
                'locale'                     => $locale,
                'label'                      => $labels[$field['code']],
                'fixed_value'                => $isFixed ? ($field['fixed'][$locale] ?? null) : null,
            ]);
        }
    }

    /**
     * Turn the passport tier on for the channels the demo publishes to. The
     * gate fails closed on a fresh install, so without this nothing publishes.
     */
    protected function enablePassports(): void
    {
        foreach (array_keys(self::PUBLISHED) as $channelCode) {
            DB::table('core_config')->updateOrInsert(
                [
                    'code'         => 'catalog.product_passport.settings.enabled',
                    'channel_code' => $channelCode,
                    'locale_code'  => null,
                ],
                ['value' => '1'],
            );
        }
    }

    protected function mapFamilies(int $templateId): void
    {
        $familyIds = DB::table('attribute_families')->pluck('id', 'code');

        foreach (self::TEMPLATE_FAMILIES as $familyCode) {
            if (! isset($familyIds[$familyCode])) {
                continue;
            }

            DB::table('passport_template_families')->updateOrInsert(
                ['attribute_family_id' => (int) $familyIds[$familyCode]],
                ['passport_template_id' => $templateId],
            );
        }
    }

    protected function publish(): void
    {
        $adminId = (int) DB::table('admins')->orderBy('id')->value('id');

        foreach (self::PUBLISHED as $channelCode => $skus) {
            $channel = Channel::query()->where('code', $channelCode)->first();

            if (! $channel instanceof Channel) {
                continue;
            }

            $locale = Locale::query()->where('code', 'en_US')->first();

            if (! $locale instanceof Locale) {
                continue;
            }

            foreach ($skus as $sku) {
                $product = Product::query()->where('sku', $sku)->first();

                if (! $product instanceof Product) {
                    continue;
                }

                try {
                    $this->publisher->publish($product, $channel, $locale, 'dpp', $adminId ?: null);
                } catch (Throwable) {
                    continue;
                }
            }
        }
    }
}

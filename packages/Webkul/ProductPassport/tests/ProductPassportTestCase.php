<?php

namespace Webkul\ProductPassport\Tests;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;
use Webkul\ProductPassport\Models\PassportTemplate;
use Webkul\Publication\DataTransferObjects\PublicationContext;
use Webkul\Publication\Models\PublicationVersion;
use Webkul\Publication\Services\Publisher;
use Webkul\User\Tests\Concerns\UserAssertions;

/**
 * Mirrors `Webkul\Publication\Tests\PublicationTestCase`'s structure.
 */
class ProductPassportTestCase extends TestCase
{
    use UserAssertions;

    /**
     * A source attribute with a unique code. The template references attributes by
     * id, so the code only has to be free — fixing it would collide with whatever
     * the catalog already holds.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function passportSourceAttribute(array $attributes = []): mixed
    {
        return AttributeProxy::factory()->create(array_merge([
            'code' => 'src_'.Str::lower(Str::random(10)),
            'type' => 'text',
        ], $attributes));
    }

    /**
     * A template bound to $family, built from a compact field list keyed by field
     * code: each entry accepts `attribute` (a source attribute), `fixed` (a
     * localized constant), `tier`, `role`, `required` and `label`.
     *
     * Templates replaced the `dpp` attribute group as the passport schema, so this
     * is what every payload fixture below configures.
     *
     * @param  array<string, array<string, mixed>>  $fields
     */
    protected function passportTemplateFor(mixed $family, array $fields, string $section = 'passport'): PassportTemplate
    {
        $template = PassportTemplate::create([
            'code'       => 'tpl_'.Str::random(8),
            'is_enabled' => true,
            'en_US'      => ['name' => 'Test passport template'],
        ]);

        $template->families()->attach($family->id);

        $sectionRow = $template->sections()->create([
            'code'     => $section,
            'position' => 0,
            'en_US'    => ['name' => 'Passport'],
        ]);

        $position = 0;

        foreach ($fields as $code => $field) {
            $isFixed = array_key_exists('fixed', $field);

            $template->fields()->create([
                'code'                         => $code,
                'passport_template_section_id' => $sectionRow->id,
                'source_type'                  => $isFixed ? 'fixed' : 'attribute',
                'attribute_id'                 => $isFixed ? null : ($field['attribute']->id ?? null),
                'tier'                         => $field['tier'] ?? 'consumer',
                'is_required'                  => (bool) ($field['required'] ?? false),
                'role'                         => $field['role'] ?? null,
                'position'                     => $position++,
                'en_US'                        => array_filter([
                    'label'       => $field['label'] ?? Str::headline($code),
                    'fixed_value' => $field['fixed'] ?? null,
                ], fn ($value): bool => $value !== null),
            ]);
        }

        return $template->refresh();
    }

    /**
     * A product carrying a passport value plus an unrelated "secret" attribute the
     * template never lists, proving only template fields reach a payload. The demo
     * document is a consumer-tier repair guide, not compliance evidence, so generic
     * document assertions stay valid under the default tier split.
     *
     * @return array{0: Product, 1: PublicationContext, 2?: string}
     */
    protected function productWithSecretAndDppAttributes(bool $withDocument = false): array
    {
        $material = $this->passportSourceAttribute(['type' => 'textarea', 'value_per_locale' => 1]);

        $secretAttribute = $this->passportSourceAttribute();

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes($material)->create();

        $channel = ChannelProxy::factory()->create();
        $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

        $values = [
            'locale_specific' => [$locale->code => [$material->code => 'Recycled cotton, 80%']],
            'common'          => [$secretAttribute->code => '4.20'],
        ];

        $fields = ['dpp_material_composition' => ['attribute' => $material, 'label' => 'Material Composition']];

        $sourcePath = null;

        if ($withDocument) {
            $guide = $this->passportSourceAttribute(['type' => 'file', 'allowed_extensions' => ['pdf']]);

            $sourcePath = 'product-files/dpp_disassembly_guide/guide.pdf';
            Storage::disk(config('filesystems.default'))->put($sourcePath, '%PDF-1.4 stub');
            $values['common'][$guide->code] = $sourcePath;

            $fields['dpp_disassembly_guide'] = ['attribute' => $guide, 'label' => 'Disassembly Guide'];
        }

        $this->passportTemplateFor($family, $fields);

        $product = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => $values,
        ]);

        $context = new PublicationContext(
            uuid: (string) Str::uuid(),
            channel: $channel,
            locale: $locale,
            url: 'https://example.test/p/'.$product->id.'/'.$locale->code,
        );

        return $withDocument ? [$product, $context, $sourcePath] : [$product, $context];
    }

    /**
     * A product whose template sources one field from the `country` attribute the
     * merchant already maintains — the fixture the value-resolution tests drive.
     * Null-valued codes are left unset so the resolver returns null for them.
     *
     * @param  array<string, mixed>  $values
     * @return array{0: Product, 1: PublicationContext}
     */
    protected function makeProductWithValues(array $values): array
    {
        $country = $this->passportSourceAttribute();

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();

        $this->passportTemplateFor($family, [
            'dpp_country_of_origin' => ['attribute' => $country, 'label' => 'Country of Origin'],
        ]);

        $channel = ChannelProxy::factory()->create();
        $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

        $mapped = [];

        foreach (array_filter($values, fn ($value): bool => $value !== null) as $code => $value) {
            $mapped[$code === 'country' ? $country->code : $code] = $value;
        }

        $product = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['common' => $mapped],
        ]);

        $context = new PublicationContext(
            uuid: (string) Str::uuid(),
            channel: $channel,
            locale: $locale,
            url: 'https://example.test/p/'.$product->id.'/'.$locale->code,
        );

        return [$product, $context];
    }

    /**
     * A configurable parent carrying a `common`-bucket value and a leaf variant with
     * none of its own — `common` is the only bucket `VariantValueResolver` merges
     * root-to-leaf.
     *
     * @return array{0: Product, 1: PublicationContext}
     */
    protected function variantWithInheritedPassportValues(): array
    {
        $manufacturer = $this->passportSourceAttribute();

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();

        $this->passportTemplateFor($family, [
            'dpp_manufacturer_name' => ['attribute' => $manufacturer, 'label' => 'Manufacturer Name'],
        ]);

        $channel = ChannelProxy::factory()->create();
        $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

        $parent = ProductProxy::factory()->configurable()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['common' => [$manufacturer->code => 'Acme Corp']],
        ]);

        $variant = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'parent_id'           => $parent->id,
            'values'              => ['common' => []],
        ]);

        $context = new PublicationContext(
            uuid: (string) Str::uuid(),
            channel: $channel,
            locale: $locale,
            url: 'https://example.test/p/'.$variant->id.'/'.$locale->code,
        );

        return [$variant, $context];
    }

    /**
     * Writes a `core_config` row directly rather than through
     * `CoreConfigRepository::create()` — that repository's
     * `recursiveArray()` uses a `static $data` cache that re-writes every
     * code seen by the *first* call in the same process on any *second*
     * call, which is exactly the kind of cross-test leakage a shared test
     * helper must not risk.
     *
     * @param  array<string, mixed>  $values
     */
    protected function setPassportConfig(array $values): void
    {
        foreach ($values as $name => $value) {
            CoreConfig::query()->updateOrCreate(
                [
                    'code'         => 'catalog.product_passport.settings.'.$name,
                    'channel_code' => null,
                    'locale_code'  => null,
                ],
                ['value' => $value],
            );
        }
    }

    /**
     * The public tier is opt-in per channel; the resolver treats an unset flag
     * as disabled, so a fixture that expects a served passport must enable it.
     */
    protected function enablePublicTier(string $channelCode): void
    {
        CoreConfig::query()->updateOrCreate(
            ['code' => 'general.publication.settings.enabled', 'channel_code' => $channelCode, 'locale_code' => null],
            ['value' => '1'],
        );
    }

    /**
     * Passport publishing is opt-in per channel; the controller reads the flag
     * scoped to the publishing channel, so publish tests must enable that code.
     */
    protected function enablePassportPublishing(string $channelCode): void
    {
        CoreConfig::query()->updateOrCreate(
            ['code' => 'catalog.product_passport.settings.enabled', 'channel_code' => $channelCode, 'locale_code' => null],
            ['value' => '1'],
        );
    }

    /**
     * Publishes through the real `Publisher`/`dpp` type (not a stub), so template
     * tests exercise the wired-together public pipeline end to end. The fixture's
     * fields are optional, so the readiness gate passes without extra setup.
     */
    protected function publishedPassportFixture(bool $withDocument = false): PublicationVersion
    {
        [$product, $context] = $this->productWithSecretAndDppAttributes($withDocument);

        $this->enablePublicTier($context->channel->code);
        $this->enablePassportPublishing($context->channel->code);

        return resolve(Publisher::class)->publish($product, $context->channel, $context->locale, 'dpp');
    }

    /**
     * Publishes a passport carrying a consumer field and an operator field, so the
     * access-tier tests can assert the operator value is gated out of the consumer
     * view yet revealed behind a valid signed `tier` URL.
     */
    protected function publishedTieredPassportFixture(): PublicationVersion
    {
        $material = $this->passportSourceAttribute(['type' => 'textarea', 'value_per_locale' => 1]);

        $notes = $this->passportSourceAttribute(['type' => 'textarea', 'value_per_locale' => 1]);

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();

        $this->passportTemplateFor($family, [
            'dpp_material_composition' => ['attribute' => $material, 'label' => 'Material Composition'],
            'dpp_supply_chain_notes'   => ['attribute' => $notes, 'label' => 'Supply Chain Notes', 'tier' => 'operator'],
        ]);

        $channel = ChannelProxy::factory()->create();
        $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

        $product = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['locale_specific' => [$locale->code => [
                $material->code => 'Recycled cotton, 80%',
                $notes->code    => 'Tier 2 supplier in Poland',
            ]]],
        ]);

        $this->enablePublicTier($channel->code);
        $this->enablePassportPublishing($channel->code);

        return resolve(Publisher::class)->publish($product, $channel, $locale, 'dpp');
    }

    /**
     * Publishes the same GTIN-bearing product across `$channelCount` channels (each
     * with its own locale), the fixture the GS1 Digital Link tests drive. The GTIN
     * rides a `common`-bucket attribute mapped to the template's gtin role, so one
     * value flows to every channel's payload — the multi-channel case the designated
     * channel setting disambiguates.
     *
     * @return array{0: Product, 1: list<Channel>, 2: list<PublicationVersion>}
     */
    protected function publishGtinPassport(string $gtin, int $channelCount = 1): array
    {
        $gtinAttribute = $this->passportSourceAttribute();

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();

        $this->passportTemplateFor($family, [
            'dpp_gtin' => ['attribute' => $gtinAttribute, 'label' => 'GTIN', 'role' => 'gtin'],
        ]);

        $product = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['common' => [$gtinAttribute->code => $gtin]],
        ]);

        /**
         * The designated passport channel is a catalogue-wide setting, and the
         * fixture publishes to channels it creates itself, so any inherited value
         * is cleared before the run and each test sets what it needs.
         */
        CoreConfig::query()->where('code', 'general.publication.settings.gs1_passport_channel')->delete();

        $channels = [];
        $versions = [];

        for ($i = 0; $i < $channelCount; $i++) {
            $channel = ChannelProxy::factory()->create();
            $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

            $this->enablePublicTier($channel->code);
            $this->enablePassportPublishing($channel->code);

            $channels[] = $channel;
            $versions[] = resolve(Publisher::class)->publish($product, $channel, $locale, 'dpp');
        }

        return [$product, $channels, $versions];
    }

    /**
     * One locale missing a required field and one carrying it, on the same channel —
     * the shape the per-locale readiness assertions need now that publishing is
     * gated on the template's required fields rather than a completeness score.
     *
     * @return array{0: Product, 1: Channel, 2: Locale, 3: Locale}
     */
    protected function productWithTwoDppLocales(): array
    {
        $material = $this->passportSourceAttribute(['type' => 'textarea', 'value_per_locale' => 1]);

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes($material)->create();

        $this->passportTemplateFor($family, [
            'dpp_material_composition' => ['attribute' => $material, 'label' => 'Material Composition', 'required' => true],
        ]);

        $channel = ChannelProxy::factory()->create();
        $complete = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

        $incomplete = Locale::factory()->create();
        $channel->locales()->attach($incomplete);

        $product = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['locale_specific' => [$complete->code => [$material->code => 'Recycled cotton, 80%']]],
        ]);

        return [$product, $channel->refresh(), $incomplete, $complete];
    }
}

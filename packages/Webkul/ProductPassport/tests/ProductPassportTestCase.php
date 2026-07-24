<?php

namespace Webkul\ProductPassport\Tests;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Attribute\Models\AttributeGroupProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;
use Webkul\ProductPassport\Database\Seeders\DppAttributeSeeder;
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
     * Seeds the `dpp` group/attributes, attaches `dpp_material_composition`
     * (and, when requested, the consumer-tier `dpp_disassembly_guide` document)
     * to a fresh family alongside an unrelated group carrying a non-dpp "secret"
     * attribute — proving the builder only ever surfaces `dpp`-group fields. The
     * demo document is deliberately a consumer-tier file (repair guide), not a
     * compliance file, so generic document-rendering assertions stay valid under
     * the default access-tier map that gates certificates to `authority`.
     *
     * @return array{0: Product, 1: PublicationContext, 2?: string}
     */
    protected function productWithSecretAndDppAttributes(bool $withDocument = false): array
    {
        resolve(DppAttributeSeeder::class)->run();

        $dppGroup = AttributeGroup::where('code', 'dpp')->firstOrFail();

        $otherGroup = AttributeGroupProxy::factory()->create();
        $secretAttribute = AttributeProxy::factory()->create([
            'code' => 'internal_cost_price',
            'type' => 'text',
        ]);

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();
        $family->familyGroups()->attach([$dppGroup->id, $otherGroup->id]);

        $family->attributeFamilyGroupMappings()->where('attribute_group_id', $dppGroup->id)->first()
            ?->customAttributes()->attach(Attribute::where('code', 'dpp_material_composition')->first());

        $family->attributeFamilyGroupMappings()->where('attribute_group_id', $otherGroup->id)->first()
            ?->customAttributes()->attach($secretAttribute);

        $channel = ChannelProxy::factory()->create();
        $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

        $values = [
            'locale_specific' => [$locale->code => ['dpp_material_composition' => 'Recycled cotton, 80%']],
            'common'          => ['internal_cost_price' => '4.20'],
        ];

        $sourcePath = null;

        if ($withDocument) {
            $family->attributeFamilyGroupMappings()->where('attribute_group_id', $dppGroup->id)->first()
                ?->customAttributes()->attach(Attribute::where('code', 'dpp_disassembly_guide')->first());

            $sourcePath = 'product-files/dpp_disassembly_guide/guide.pdf';
            Storage::disk(config('filesystems.default'))->put($sourcePath, '%PDF-1.4 stub');
            $values['common']['dpp_disassembly_guide'] = $sourcePath;
        }

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
     * A product in the `dpp` family carrying `common`-bucket values keyed by
     * attribute code, alongside a non-dpp `country` source attribute — the
     * fixture the field-mapping builder tests drive: set a value on the source
     * (or the dpp field) and assert which one the payload surfaces. Null-valued
     * codes are left unset so `getValueFromProductValues` returns null for them.
     *
     * @param  array<string, mixed>  $values
     * @return array{0: Product, 1: PublicationContext}
     */
    protected function makeProductWithValues(array $values): array
    {
        resolve(DppAttributeSeeder::class)->run();

        $dppGroup = AttributeGroup::where('code', 'dpp')->firstOrFail();

        $sourceGroup = AttributeGroupProxy::factory()->create();
        $sourceAttribute = AttributeProxy::factory()->create([
            'code' => 'country',
            'type' => 'text',
        ]);

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();
        $family->familyGroups()->attach([$dppGroup->id, $sourceGroup->id]);

        $family->attributeFamilyGroupMappings()->where('attribute_group_id', $dppGroup->id)->first()
            ?->customAttributes()->attach(Attribute::where('code', 'dpp_country_of_origin')->first());

        $family->attributeFamilyGroupMappings()->where('attribute_group_id', $sourceGroup->id)->first()
            ?->customAttributes()->attach($sourceAttribute);

        $channel = ChannelProxy::factory()->create();
        $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

        $common = array_filter($values, fn ($value): bool => $value !== null);

        $product = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['common' => $common],
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
     * A configurable parent carrying a `common`-bucket dpp value and a leaf
     * variant with no value of its own — `common` is the only bucket
     * `VariantValueResolver::mergeChain()` actually merges root-to-leaf.
     *
     * @return array{0: Product, 1: PublicationContext}
     */
    protected function variantWithInheritedPassportValues(): array
    {
        resolve(DppAttributeSeeder::class)->run();

        $dppGroup = AttributeGroup::where('code', 'dpp')->firstOrFail();
        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();
        $family->familyGroups()->attach($dppGroup->id);
        $family->attributeFamilyGroupMappings()->where('attribute_group_id', $dppGroup->id)->first()
            ?->customAttributes()->attach(Attribute::where('code', 'dpp_manufacturer_name')->first());

        $channel = ChannelProxy::factory()->create();
        $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

        $parent = ProductProxy::factory()->configurable()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['common' => ['dpp_manufacturer_name' => 'Acme Corp']],
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
            CoreConfig::create([
                'code'  => 'catalog.product_passport.settings.'.$name,
                'value' => $value,
            ]);
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
     * Publishes through the real `Publisher`/`dpp` type (not a stub), so
     * template tests exercise the actual, wired-together public pipeline
     * end to end. Registers a perfect completeness score directly since
     * these tests aren't exercising `CompletenessGate` itself.
     */
    protected function publishedPassportFixture(bool $withDocument = false): PublicationVersion
    {
        [$product, $context] = $this->productWithSecretAndDppAttributes($withDocument);

        ProductCompletenessScore::query()->create([
            'product_id'    => $product->id,
            'channel_id'    => $context->channel->id,
            'locale_id'     => $context->locale->id,
            'score'         => 100,
            'missing_count' => 0,
        ]);

        $this->enablePublicTier($context->channel->code);

        return resolve(Publisher::class)->publish($product, $context->channel, $context->locale, 'dpp');
    }

    /**
     * Publishes a passport carrying both a base-tier field
     * (`dpp_material_composition`) and an operator-tier field
     * (`dpp_supply_chain_notes`, classified `operator` in `passport.tiers.map`),
     * so access-tier tests can assert the operator value is gated out of the
     * consumer view yet revealed behind a valid signed `tier` URL.
     */
    protected function publishedTieredPassportFixture(): PublicationVersion
    {
        resolve(DppAttributeSeeder::class)->run();

        $dppGroup = AttributeGroup::where('code', 'dpp')->firstOrFail();

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();
        $family->familyGroups()->attach($dppGroup->id);

        $mapping = $family->attributeFamilyGroupMappings()->where('attribute_group_id', $dppGroup->id)->first();
        $mapping?->customAttributes()->attach(Attribute::where('code', 'dpp_material_composition')->first());
        $mapping?->customAttributes()->attach(Attribute::where('code', 'dpp_supply_chain_notes')->first());

        $channel = ChannelProxy::factory()->create();
        $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

        $product = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['locale_specific' => [$locale->code => [
                'dpp_material_composition' => 'Recycled cotton, 80%',
                'dpp_supply_chain_notes'   => 'Tier 2 supplier in Poland',
            ]]],
        ]);

        ProductCompletenessScore::query()->create([
            'product_id'    => $product->id,
            'channel_id'    => $channel->id,
            'locale_id'     => $locale->id,
            'score'         => 100,
            'missing_count' => 0,
        ]);

        $this->enablePublicTier($channel->code);

        return resolve(Publisher::class)->publish($product, $channel, $locale, 'dpp');
    }

    /**
     * Publishes the same GTIN-bearing product across `$channelCount` channels
     * (each with its own locale), the fixture the GS1 Digital Link tests drive.
     * `dpp_gtin` is a common-bucket attribute, so one value flows to every
     * channel's payload — the multi-channel case the designated-channel setting
     * disambiguates. Returns the product alongside the created channels and the
     * per-channel published versions in creation order (channel_id ascending).
     *
     * @return array{0: Product, 1: list<Channel>, 2: list<PublicationVersion>}
     */
    protected function publishGtinPassport(string $gtin, int $channelCount = 1): array
    {
        resolve(DppAttributeSeeder::class)->run();

        $dppGroup = AttributeGroup::where('code', 'dpp')->firstOrFail();

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();
        $family->familyGroups()->attach($dppGroup->id);
        $family->attributeFamilyGroupMappings()->where('attribute_group_id', $dppGroup->id)->first()
            ?->customAttributes()->attach(Attribute::where('code', 'dpp_gtin')->first());

        $product = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => ['common' => ['dpp_gtin' => $gtin]],
        ]);

        $channels = [];
        $versions = [];

        for ($i = 0; $i < $channelCount; $i++) {
            $channel = ChannelProxy::factory()->create();
            $locale = $channel->locales()->first() ?: tap(Locale::factory()->create(), fn ($l) => $channel->locales()->attach($l));

            ProductCompletenessScore::query()->create([
                'product_id'    => $product->id,
                'channel_id'    => $channel->id,
                'locale_id'     => $locale->id,
                'score'         => 100,
                'missing_count' => 0,
            ]);

            $this->enablePublicTier($channel->code);

            $channels[] = $channel;
            $versions[] = resolve(Publisher::class)->publish($product, $channel, $locale, 'dpp');
        }

        return [$product, $channels, $versions];
    }

    /**
     * An incomplete and a complete locale on the same channel, mirroring the
     * shape `Webkul\Publication`'s own (package-local) `seedPassportFixture()`
     * uses — `ProductPassportTestCase` cannot call a protected method
     * defined on a different package's test case.
     *
     * @return array{0: Product, 1: Channel, 2: Locale, 3: Locale}
     */
    protected function productWithTwoDppLocales(): array
    {
        [$product, $context] = $this->productWithSecretAndDppAttributes();

        $incomplete = Locale::factory()->create();
        $context->channel->locales()->attach($incomplete);

        ProductCompletenessScore::query()->create([
            'product_id' => $product->id, 'channel_id' => $context->channel->id,
            'locale_id'  => $context->locale->id, 'score' => 100, 'missing_count' => 0,
        ]);

        ProductCompletenessScore::query()->create([
            'product_id' => $product->id, 'channel_id' => $context->channel->id,
            'locale_id'  => $incomplete->id, 'score' => 40, 'missing_count' => 3,
        ]);

        return [$product, $context->channel, $incomplete, $context->locale];
    }
}

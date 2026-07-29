<?php

namespace Webkul\Publication\Tests;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeGroupProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Completeness\Models\CompletenessSetting;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;
use Webkul\Publication\Models\PublicationVersion;
use Webkul\Publication\Providers\PublicationServiceProvider;
use Webkul\Publication\Services\Publisher;
use Webkul\Publication\Tests\Support\DocumentStubPayloadBuilder;
use Webkul\Publication\Tests\Support\StubPayloadBuilder;
use Webkul\User\Tests\Concerns\UserAssertions;

class PublicationTestCase extends TestCase
{
    use UserAssertions;

    /**
     * Completeness rows are left to the real engine, not hand-seeded: any later $product->save() would overwrite them.
     *
     * @return array{0: Product, 1: Channel, 2: Locale, 3: Locale}
     */
    protected function seedPassportFixture(bool $completeBoth = false): array
    {
        $this->loginAsAdmin();

        $channel = ChannelProxy::factory()->create();

        if ($channel->locales()->count() < 2) {
            $channel->locales()->attach(Locale::factory()->create(['status' => 1]));
            $channel->refresh();
        }

        [$incomplete, $complete] = $channel->locales()->get()->all();

        $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();

        $group = AttributeGroupProxy::factory()->create();
        $family->familyGroups()->attach($group);

        $attribute = AttributeProxy::factory()->create([
            'code'              => 'dpp_material_composition',
            'type'              => 'text',
            'is_required'       => 0,
            'value_per_locale'  => 1,
            'value_per_channel' => 0,
        ]);

        $family->attributeFamilyGroupMappings()
            ->where('attribute_group_id', $group->id)
            ->first()
            ?->customAttributes()
            ->attach($attribute);

        // Without a CompletenessSetting the observer treats the channel as unconfigured and deletes its score on save.
        CompletenessSetting::query()->create([
            'family_id'    => $family->id,
            'attribute_id' => $attribute->id,
            'channel_id'   => $channel->id,
        ]);

        $localeSpecific = [
            $complete->code => [
                'dpp_material_composition' => 'Recycled cotton, 80%',
            ],
        ];

        if ($completeBoth) {
            $localeSpecific[$incomplete->code] = [
                'dpp_material_composition' => 'Organic hemp, 60%',
            ];
        }

        $product = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => [
                'locale_specific' => $localeSpecific,
            ],
        ]);

        return [$product, $channel, $incomplete, $complete];
    }

    protected function publishedPassportFixture(): PublicationVersion
    {
        [$product, $channel, , $complete] = $this->seedPassportFixture();

        config()->set('publication.types.dpp', [
            'label'           => 'publication::app.publications.status.published',
            'payload_builder' => StubPayloadBuilder::class,
            'template'        => 'publication::public.stub',
            'required_group'  => 'dpp',
            'route_prefix'    => 'p',
        ]);

        // boot() already ran before the `dpp` type was configured, so re-register to create the `/p/...` routes.
        $this->app->getProvider(PublicationServiceProvider::class)->registerPublicRoutes();

        $this->enablePublicTier($channel->code);

        return resolve(Publisher::class)->publish($product, $channel, $complete, 'dpp');
    }

    /**
     * Public tier is opt-in per channel; an unset flag reads as disabled.
     */
    protected function enablePublicTier(string $channelCode): void
    {
        CoreConfig::query()->updateOrCreate(
            ['code' => 'general.publication.settings.enabled', 'channel_code' => $channelCode, 'locale_code' => null],
            ['value' => '1'],
        );
    }

    /**
     * Places a real file on the asset disk before publishing so the payload path exists when documents are indexed.
     *
     * @return array{0: PublicationVersion, 1: string}
     */
    protected function passportWithDocumentFixture(): array
    {
        [$product, $channel, , $complete] = $this->seedPassportFixture();

        $path = 'publication/'.$product->id.'/'.$complete->code.'/certificate.pdf';

        Storage::disk(config('publication.asset_disk'))->put($path, '%PDF-1.4 stub');

        config()->set('publication.types.dpp', [
            'label'           => 'publication::app.publications.status.published',
            'payload_builder' => DocumentStubPayloadBuilder::class,
            'template'        => 'publication::public.stub',
            'required_group'  => 'dpp',
            'route_prefix'    => 'p',
        ]);

        // See publishedPassportFixture(): re-register so the just-added `dpp` type gets its routes.
        $this->app->getProvider(PublicationServiceProvider::class)->registerPublicRoutes();

        $this->enablePublicTier($channel->code);

        DocumentStubPayloadBuilder::$documentPath = $path;

        $version = resolve(Publisher::class)->publish($product, $channel, $complete, 'dpp');

        return [$version, $path];
    }
}

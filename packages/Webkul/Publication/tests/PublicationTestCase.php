<?php

namespace Webkul\Publication\Tests;

use Tests\TestCase;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeGroupProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;
use Webkul\User\Tests\Concerns\UserAssertions;

class PublicationTestCase extends TestCase
{
    use UserAssertions;

    /**
     * Seeds a channel with two locales, an attribute group carrying one
     * `value_per_locale` attribute, a product with a value in only one of
     * those locales, and matching `product_completeness` rows.
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

        $product = ProductProxy::factory()->create([
            'attribute_family_id' => $family->id,
            'values'              => [
                'locale_specific' => [
                    $complete->code => [
                        'dpp_material_composition' => 'Recycled cotton, 80%',
                    ],
                ],
            ],
        ]);

        ProductCompletenessScore::query()->create([
            'product_id'    => $product->id,
            'channel_id'    => $channel->id,
            'locale_id'     => $complete->id,
            'score'         => 100,
            'missing_count' => 0,
        ]);

        if ($completeBoth) {
            ProductCompletenessScore::query()->create([
                'product_id'    => $product->id,
                'channel_id'    => $channel->id,
                'locale_id'     => $incomplete->id,
                'score'         => 100,
                'missing_count' => 0,
            ]);
        }

        return [$product, $channel, $incomplete, $complete];
    }
}

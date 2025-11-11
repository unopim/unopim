<?php

use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Completeness\Jobs\ProductCompletenessJob;
use Webkul\Completeness\Models\CompletenessSettingProxy;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductProxy;

beforeEach(function () {
    $this->loginAsAdmin();
});

/**
 * Ensure product completeness job processes products and saves scores
 */
it('calculates and stores completeness scores for a product', function () {
    $channel = ChannelProxy::first();
    $locale = $channel->locales()->first();

    $attribute = AttributeProxy::factory()->create([
        'type'              => 'text',
        'is_required'       => 1,
        'value_per_channel' => 1,
        'value_per_locale'  => 0,
    ]);

    $familyId = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create()->id;

    $product = ProductProxy::factory()->create([
        'attribute_family_id' => $familyId,
        'values'              => [
            'channel_specific' => [
                $channel->code => [
                    $attribute->code => 'Some Value',
                ],
            ],
        ],
    ]);

    CompletenessSettingProxy::factory()->create([
        'family_id'    => $familyId,
        'attribute_id' => $attribute->id,
        'channel_id'   => $channel->id,
    ]);

    ProductCompletenessJob::dispatchSync([$product->id]);

    $this->assertDatabaseHas('product_completeness', [
        'product_id' => $product->id,
        'channel_id' => $channel->id,
        'locale_id'  => $locale->id,
        'score'      => 100,
    ]);

    $this->assertDatabaseHas('products', [
        'id'                     => $product->id,
        'avg_completeness_score' => 100,
    ]);
});

it('calculates completeness correctly for localizable and non-localizable attributes across locales', function () {
    $channel = ChannelProxy::factory()->create(['code' => 'test_channel__']);

    if ($channel->locales()->count() !== 2) {
        $channel->locales()->attach(Locale::factory()->create(['status' => 1]));
    }

    $locales = $channel->refresh()->locales()->get();

    $locale1 = $locales[0];

    $locale2 = $locales[1];

    $nonLocalizable = AttributeProxy::factory()->create([
        'type'             => 'text',
        'is_required'      => 0,
        'value_per_locale' => 0,
        'value_per_channel'=> 0,
    ]);

    $localizable = AttributeProxy::factory()->create([
        'type'             => 'text',
        'is_required'      => 0,
        'value_per_locale' => 1,
        'value_per_channel'=> 1,
    ]);

    $familyId = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create()->id;

    $product = ProductProxy::factory()->create([
        'attribute_family_id' => $familyId,
        'values'              => [
            'channel_locale_specific' => [
                'test_channel__' => [
                    $locale1->code => [
                        $localizable->code => 'Value Locale 1',
                    ],
                    $locale2->code => [
                        // Missing value for locale 2
                    ],
                ],
            ],
            'common' => [
                $nonLocalizable->code => 'Non Localized Value',
            ],
        ],
    ]);

    foreach ([$nonLocalizable, $localizable] as $attribute) {
        CompletenessSettingProxy::factory()->create([
            'family_id'    => $familyId,
            'attribute_id' => $attribute->id,
            'channel_id'   => $channel->id,
        ]);
    }

    ProductCompletenessJob::dispatchSync([$product->id]);

    $this->assertDatabaseHas('product_completeness', [
        'product_id' => $product->id,
        'channel_id' => $channel->id,
        'locale_id'  => $locale1->id,
        'score'      => 100,
    ]);

    $this->assertDatabaseHas('product_completeness', [
        'product_id' => $product->id,
        'channel_id' => $channel->id,
        'locale_id'  => $locale2->id,
        'score'      => 50,
    ]);

    $this->assertDatabaseHas('products', [
        'id'                     => $product->id,
        'avg_completeness_score' => 75,
    ]);
});

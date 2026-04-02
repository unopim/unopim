<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Services\Prompt\ProductPrompt;
use Webkul\Product\Models\Product;

beforeEach(function () {
    ProductPrompt::resetInstance();

    $this->loginAsAdmin();

    // Create a default platform for tests
    MagicAIPlatform::query()->delete();
    MagicAIPlatform::create([
        'label'      => 'Test Platform',
        'provider'   => 'groq',
        'api_url'    => 'https://api.groq.com/openai/v1',
        'api_key'    => 'test-key',
        'models'     => 'qwen-qwq-32b,deepseek-r1-distill-llama-70b',
        'is_default' => true,
        'status'     => true,
    ]);

    // Enable Magic AI
    app(CoreConfigRepository::class)->create([
        'general' => [
            'magic_ai' => [
                'settings' => [
                    'enabled' => '1',
                ],
            ],
        ],
    ]);
});

it('should translate the field successfully', function () {
    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE', 'en_US'])->update(['status' => 1]);

    $defaultChannel = core()->getDefaultChannel();
    $defaultChannelLocale = $defaultChannel->locales->first()->code;

    $attribute = Attribute::factory()->create(['value_per_locale' => true, 'value_per_channel' => true, 'type' => 'text']);
    $attributeCode = $attribute->code;

    $product = Product::factory()->simple()->create([
        'values' => [
            'channel_locale_specific' => [
                'default' => [
                    $defaultChannelLocale => [
                        $attributeCode => 'Default Channel Value',
                        'name'         => 'smartPhone',
                    ],
                ],
            ],
        ],
    ]);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);
    $productId = $product->id;
    $field = 'name';
    $locale = $defaultChannelLocale;
    $channel = 'default';
    $model = 'qwen-qwq-32b';
    $resourceType = 'product';
    $targetChannel = 'default';
    $targetLocale = 'af_ZA';

    MagicAI::shouldReceive('useDefault')->andReturnSelf();
    MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
    MagicAI::shouldReceive('setModel')
        ->with($model)
        ->andReturnSelf();
    MagicAI::shouldReceive('setPrompt')
        ->andReturnSelf();
    MagicAI::shouldReceive('ask')
        ->andReturn('<p>translated_content</p>');

    $this->post(route('admin.magic_ai.translate', [
        'resource_id'   => $productId,
        'field'         => $field,
        'locale'        => $locale,
        'channel'       => $channel,
        'model'         => $model,
        'resource_type' => $resourceType,
        'targetChannel' => $targetChannel,
        'targetLocale'  => $targetLocale,
    ]))
        ->assertOk()
        ->assertJsonStructure([
            'translatedData' => [
                '*' => [
                    'locale',
                    'content',
                ],
            ],
        ]);

    $expectedTranslatedData = [
        [
            'locale'  => 'af_ZA',
            'content' => 'translated_content_for_af_ZA',
        ],
    ];

    $formData = [
        'resource_id'    => $productId,
        'resource_type'  => 'product',
        'field'          => $field,
        'translatedData' => json_encode($expectedTranslatedData),
        'targetChannel'  => $targetChannel,
    ];

    $this->post(route('admin.magic_ai.store.translated'), $formData)
        ->assertOk()
        ->assertJson(['message' => trans('admin::app.catalog.products.edit.translate.tranlated-job-processed')]);
});

it('should check if the field is translatable successfully', function () {
    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE', 'en_US'])->update(['status' => 1]);

    $defaultChannel = core()->getDefaultChannel();

    $defaultChannelLocale = $defaultChannel->locales->first()->code;

    $attribute = Attribute::factory()->create(['value_per_locale' => true, 'value_per_channel' => true, 'type' => 'text']);

    $attributeCode = $attribute->code;

    $product = Product::Factory()->simple()->create([
        'values' => [
            'channel_locale_specific' => [
                'default' => [
                    $defaultChannelLocale => [
                        $attributeCode => 'Default Channel Value',
                        'name'         => 'smartPhone',
                    ],
                ],
            ],
        ],
    ]);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);
    $productId = $product->id;
    $field = 'name';
    $locale = $defaultChannelLocale;
    $channel = 'default';

    $this->post(route('admin.magic_ai.check.is_translatable', [
        'resource_id' => $productId,
        'field'       => $field,
        'locale'      => $locale,
        'channel'     => $channel,
    ]))
        ->assertOk()
        ->assertJson(['isTranslatable' => true]);
});

it('should translate all attributes successfully', function () {
    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE', 'en_US'])->update(['status' => 1]);

    $defaultChannel = core()->getDefaultChannel();
    $defaultChannelLocale = $defaultChannel->locales->first()->code;

    $attribute = Attribute::factory()->create([
        'value_per_locale'  => true,
        'value_per_channel' => true,
        'type'              => 'text',
    ]);

    $attributeCode = $attribute->code;

    $product = Product::factory()->simple()->create([
        'values' => [
            'channel_locale_specific' => [
                'default' => [
                    $defaultChannelLocale => [
                        $attributeCode => 'smartphone',
                        'name'         => 'smartPhone',
                        'description'  => 'This phone is very interesting.',
                    ],
                ],
            ],
        ],
    ]);

    $product->attribute_family
        ->attributeFamilyGroupMappings
        ->first()?->customAttributes()?->attach($attribute);

    $productId = $product->id;
    $locale = $defaultChannelLocale;
    $channel = 'default';
    $model = 'qwen-qwq-32b';
    $resourceType = 'product';
    $targetChannel = 'default';
    $targetLocale = 'af_ZA';
    $attributes = implode(',', ['name', 'description']);

    MagicAI::shouldReceive('useDefault')->andReturnSelf();
    MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
    MagicAI::shouldReceive('setModel')->with($model)->andReturnSelf();
    MagicAI::shouldReceive('setPrompt')->andReturnSelf();
    MagicAI::shouldReceive('ask')->andReturn('<p>test</p>');

    $this->post(route('admin.magic_ai.translate.all.attribute', [
        'resource_id'   => $productId,
        'attributes'    => $attributes,
        'locale'        => $locale,
        'channel'       => $channel,
        'model'         => $model,
        'resource_type' => $resourceType,
        'targetChannel' => $targetChannel,
        'targetLocale'  => $targetLocale,
    ]))
        ->assertOk()
        ->assertJsonStructure([
            'headers',
            'fields',
            'translated',
        ]);
});

it('should save all translated attributes successfully', function () {
    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE', 'en_US'])->update(['status' => 1]);

    $defaultChannel = core()->getDefaultChannel();
    $defaultChannelLocale = $defaultChannel->locales->first()->code;

    $attribute = Attribute::factory()->create([
        'value_per_locale'  => true,
        'value_per_channel' => true,
        'type'              => 'text',
    ]);

    $attributeCode = $attribute->code;

    $product = Product::factory()->simple()->create([
        'values' => [
            'channel_locale_specific' => [
                'default' => [
                    $defaultChannelLocale => [
                        $attributeCode => 'smartphone',
                        'name'         => 'smartPhone',
                    ],
                ],
            ],
        ],
    ]);

    $product->attribute_family
        ->attributeFamilyGroupMappings
        ->first()?->customAttributes()?->attach($attribute);

    $productId = $product->id;
    $targetChannel = 'default';

    $translatedValues = [
        [
            'field'        => 'name',
            'translations' => [
                ['locale' => 'af_ZA', 'content' => 'Translated Name'],
            ],
        ],
    ];

    $this->post(route('admin.magic_ai.store.translated.all_attribute'), [
        'resource_id'    => $productId,
        'translatedData' => json_encode($translatedValues),
        'targetChannel'  => $targetChannel,
    ])
        ->assertOk()
        ->assertJson(['message' => trans('admin::app.catalog.products.edit.translate.tranlated-job-processed')]);
});

<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;

it('should check if the field is translatable successfully', function () {
    $this->loginAsAdmin();
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
                        'name'         => 'Ravindra',
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

it('should it translate  the field  successfully', function () {
    $this->loginAsAdmin();
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

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.settings';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled'       => '1',
        'api_key'       => '', // replace with api_key then it will work.
        'organization'  => 'org-9',
        'api_domain'    => 'api.groq.com',
        'ai_platform'   => 'groq',
    ];

    $data['general']['magic_ai']['settings'] = $fields;

    foreach ($fields as $field) {
        $data['keys'][] = $configData;
    }

    $response = $this->post(route('admin.configuration.store'), [
        ...$data,
    ])->assertRedirect();
    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);
    $productId = $product->id;
    $field = 'name';
    $locale = $defaultChannelLocale;
    $channel = 'default';
    $model = 'qwen-qwq-32b';
    $resourceType = 'product';
    $resourceType = 'product';
    $targetChannel = 'default';
    $targetLocale = 'af_ZA';

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
        ->assertJson(['message' =>trans('admin::app.catalog.products.edit.translate.tranlated-job-processed')]);
});

it('should it translate  All Attribute  successfully', function () {
    $this->loginAsAdmin();
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
                        'description'  => 'This phone is very intresting.',
                    ],
                ],
            ],
        ],
    ]);

    $data = [];

    $configData = include __DIR__.'/../../../src/Config/system.php';

    $configData = array_filter($configData, function ($item) {
        return ($item['key'] ?? '') == 'general.magic_ai.settings';
    });

    $configData = json_encode(current($configData));

    $fields = [
        'enabled'       => '1',
        'api_key'       => '', // replace with api_key then it will work.
        'organization'  => 'org-9',
        'api_domain'    => 'api.groq.com',
        'ai_platform'   => 'groq',
    ];

    $data['general']['magic_ai']['settings'] = $fields;

    foreach ($fields as $field) {
        $data['keys'][] = $configData;
    }

    $response = $this->post(route('admin.configuration.store'), [
        ...$data,
    ])->assertRedirect();
    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);
    $productId = $product->id;
    $locale = $defaultChannelLocale;
    $channel = 'default';
    $model = 'qwen-qwq-32b';
    $resourceType = 'product';
    $targetChannel = 'default';
    $targetLocale = 'af_ZA';
    $field = ['name', 'description'];
    $attributes = implode(',', $field);

    $this->post(route('admin.magic_ai.translate.all.attribute', [
        'resource_id'   => $productId,
        'locale'        => $locale,
        'channel'       => $channel,
        'model'         => $model,
        'resource_type' => $resourceType,
        'targetChannel' => $targetChannel,
        'targetLocale'  => $targetLocale,
        'attributes'    => $attributes,
    ]))
        ->assertOk()
        ->assertJsonStructure([
            '*' => [
                'fieldName',
                'isTranslatable',
                'sourceData',
                'translatedData' => [
                    '*' => [
                        'locale',
                        'content',
                    ],
                ],
            ],
        ]);

    $translatedData = [
        [
            'field'          => 'name',
            'isTranslatable' => true,
            'sourceData'     => 'smartPhone',
            'translations'   => [
                ['locale' => 'af_ZA', 'content' => 'smatfon'],
            ],
        ],
        [
            'field'          => 'description',
            'isTranslatable' => true,
            'sourceData'     => 'This phone is very interesting.',
            'translations'   => [
                ['locale' => 'fr', 'content' => 'Hierdie telefoon is baie interessant.'],
            ],
        ],
    ];

    $this->post(route('admin.magic_ai.store.translated.all_attribute'), [
        'resource_id'    => $productId,
        'targetChannel'  => $targetChannel,
        'translatedData' => json_encode($translatedData),
    ])
        ->assertOk()
        ->assertJson(['message' =>trans('admin::app.catalog.products.edit.translate.tranlated-job-processed')]);
});

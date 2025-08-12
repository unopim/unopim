<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Locale;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\MagicAI\Services\Prompt\ProductPrompt;
use Webkul\Product\Models\Product;

beforeEach(function () {
    ProductPrompt::resetInstance();
});

it('should translate all attributes successfully', function () {
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
                        $attributeCode => 'smartphone',
                        'name'         => 'smartPhone',
                        'description'  => 'This phone is very intresting.',
                    ],
                ],
            ],
        ],
    ]);

    $coreConfigRepository = app(CoreConfigRepository::class);
    $coreConfigRepository->create([
        'general' => [
            'magic_ai' => [
                'settings' => [
                    'enabled'     => '1',
                    'ai_platform' => 'groq',
                    'api_domain'  => 'api.groq.com',
                    'api_model'   => 'deepseek-r1-distill-llama-70b',
                ],
            ],
        ],
    ]);

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

    MagicAI::shouldReceive('setModel')
        ->with($model)
        ->andReturnSelf();
    MagicAI::shouldReceive('setPlatForm')
        ->with(core()->getConfigData('general.magic_ai.settings.ai_platform'))
        ->andReturnSelf();
    MagicAI::shouldReceive('setPrompt')
        ->andReturnSelf();
    MagicAI::shouldReceive('ask')
        ->andReturn('<p>translated_content</p>');

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
            'sourceData'     => '',
            'translations'   => [
                ['locale' => 'af_ZA', 'content' => 'Nom smatfon'],
            ],
        ],
        [
            'field'          => 'description',
            'isTranslatable' => true,
            'sourceData'     => 'This phone is very intresting.',
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
        ->assertJson(['message' => trans('admin::app.catalog.products.edit.translate.tranlated-job-processed')]);
});

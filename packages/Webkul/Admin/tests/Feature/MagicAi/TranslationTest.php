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
    MagicAI::shouldReceive('translate')
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
    MagicAI::shouldReceive('translate')->andReturn('<p>test</p>');

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

it('treats a locale only attribute as translatable and saves the translation in the locale bucket', function () {
    Locale::whereIn('code', ['fr_FR', 'en_US'])->update(['status' => 1]);

    $defaultChannel = core()->getDefaultChannel();
    $defaultChannelLocale = $defaultChannel->locales->first()->code;

    $attribute = Attribute::factory()->create([
        'value_per_locale'  => true,
        'value_per_channel' => false,
        'type'              => 'textarea',
    ]);

    $product = Product::factory()->simple()->create([
        'values' => [
            'locale_specific' => [
                $defaultChannelLocale => [
                    $attribute->code => '220 gsm · Organic cotton',
                ],
            ],
        ],
    ]);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $this->post(route('admin.magic_ai.check.is_translatable', [
        'resource_id' => $product->id,
        'field'       => $attribute->code,
        'locale'      => $defaultChannelLocale,
        'channel'     => 'default',
    ]))
        ->assertOk()
        ->assertJson([
            'isTranslatable' => true,
            'sourceData'     => '220 gsm · Organic cotton',
        ]);

    $this->post(route('admin.magic_ai.store.translated'), [
        'resource_id'    => $product->id,
        'resource_type'  => 'product',
        'field'          => $attribute->code,
        'translatedData' => json_encode([['locale' => 'fr_FR', 'content' => '220 g/m² · Coton biologique']]),
        'targetChannel'  => 'default',
    ])->assertOk();

    $values = $product->refresh()->values;

    expect($values['locale_specific']['fr_FR'][$attribute->code])->toBe('220 g/m² · Coton biologique')
        ->and($values['channel_locale_specific'] ?? [])->toBe([]);
});

function fakeMagicAiTranslation(array|string $responses, array &$fieldTypes): void
{
    MagicAI::shouldReceive('useDefault')->andReturnSelf();
    MagicAI::shouldReceive('setPlatformId')->andReturnSelf();
    MagicAI::shouldReceive('setModel')->andReturnSelf();
    MagicAI::shouldReceive('setPrompt')
        ->withArgs(function (...$args) use (&$fieldTypes) {
            $fieldTypes[] = $args[1] ?? 'tinymce';

            return true;
        })
        ->andReturnSelf();
    MagicAI::shouldReceive('translate')->andReturn(...(array) $responses);
}

it('returns plain text without markup when translating a plain attribute', function (string $response, string $expected) {
    $attribute = Attribute::factory()->create(['value_per_locale' => true, 'type' => 'textarea', 'enable_wysiwyg' => false]);

    $product = Product::factory()->simple()->create();

    $fieldTypes = [];

    fakeMagicAiTranslation($response, $fieldTypes);

    $this->post(route('admin.magic_ai.translate'), [
        'resource_id'   => $product->id,
        'resource_type' => 'product',
        'field'         => $attribute->code,
        'model'         => 'qwen-qwq-32b',
        'targetLocale'  => 'fr_FR',
    ])
        ->assertOk()
        ->assertJsonPath('translatedData.0.content', $expected);

    expect($fieldTypes)->toBe(['text']);
})->with([
    'paragraph wrapper'        => ['<p>Chemise en coton</p>', 'Chemise en coton'],
    'split separated keywords' => ['<p>chemise</p><p>coton</p>', "chemise\ncoton"],
    'mixed block tags'         => ['<p>Chemise</p><ul><li>coton</li><li>bio</li></ul>', "Chemise\ncoton\nbio"],
    'line breaks'              => ['Chemise<br>coton', "Chemise\ncoton"],
    'entities'                 => ['<p>Tom &amp; Jerry</p>', 'Tom & Jerry'],
    'literal angle bracket'    => ['Taille <5cm', 'Taille <5cm'],
]);

it('keeps the markup when translating a rich text attribute', function () {
    $attribute = Attribute::factory()->create(['value_per_locale' => true, 'type' => 'textarea', 'enable_wysiwyg' => true]);

    $product = Product::factory()->simple()->create();

    $fieldTypes = [];

    fakeMagicAiTranslation('<p>Chemise <strong>bio</strong></p>', $fieldTypes);

    $this->post(route('admin.magic_ai.translate'), [
        'resource_id'   => $product->id,
        'resource_type' => 'product',
        'field'         => $attribute->code,
        'model'         => 'qwen-qwq-32b',
        'targetLocale'  => 'fr_FR',
    ])
        ->assertOk()
        ->assertJsonPath('translatedData.0.content', '<p>Chemise <strong>bio</strong></p>');

    expect($fieldTypes)->toBe(['tinymce']);
});

it('treats plain and rich text attributes separately when translating all attributes', function () {
    Locale::whereIn('code', ['fr_FR', 'en_US'])->update(['status' => 1]);

    $locale = core()->getDefaultChannel()->locales->first()->code;

    $plain = Attribute::factory()->create(['value_per_locale' => true, 'type' => 'text']);
    $rich = Attribute::factory()->create(['value_per_locale' => true, 'type' => 'textarea', 'enable_wysiwyg' => true]);

    $product = Product::factory()->simple()->create([
        'values' => [
            'locale_specific' => [
                $locale => [
                    $plain->code => 'Cotton shirt',
                    $rich->code  => '<p>Organic <strong>cotton</strong></p>',
                ],
            ],
        ],
    ]);

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach([$plain->id, $rich->id]);

    $fieldTypes = [];

    fakeMagicAiTranslation(['<p>Chemise en coton</p>', '<p>Coton <strong>bio</strong></p>'], $fieldTypes);

    $this->post(route('admin.magic_ai.translate.all.attribute'), [
        'resource_id'   => $product->id,
        'resource_type' => 'product',
        'attributes'    => implode(',', [$plain->code, $rich->code]),
        'locale'        => $locale,
        'channel'       => 'default',
        'model'         => 'qwen-qwq-32b',
        'targetChannel' => 'default',
        'targetLocale'  => 'fr_FR',
    ])
        ->assertOk()
        ->assertJsonPath("translated.fr_FR.{$plain->code}.content", 'Chemise en coton')
        ->assertJsonPath("translated.fr_FR.{$rich->code}.content", '<p>Coton <strong>bio</strong></p>');

    expect($fieldTypes)->toBe(['text', 'tinymce']);
});

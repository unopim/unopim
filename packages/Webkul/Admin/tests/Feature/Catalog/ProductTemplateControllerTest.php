<?php

use Webkul\Admin\Http\Controllers\Catalog\ProductTemplateController;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(InteractsWithTemplates::class);

beforeEach(function () {
    $this->loginAsAdmin();

    $this->channel = $this->templateChannel(['EUR', 'USD'], ['en_US', 'de_DE']);

    $this->name = $this->variableAttribute('tpl_name', 'text', ['value_per_locale' => 1]);
    $this->color = $this->variableAttribute('tpl_color', 'select');
    $this->price = $this->variableAttribute('tpl_price', 'price');
    $this->image = $this->variableAttribute('tpl_image', 'image');
    $this->outsider = $this->variableAttribute('tpl_outsider');
    $this->description = $this->templateAttribute('tpl_description');
    $this->shortDescription = $this->templateAttribute('tpl_short_description');
    $this->richDescription = $this->templateAttribute('tpl_rich_description', ['enable_wysiwyg' => 1]);

    foreach (['en_US' => 'Product Name', 'de_DE' => 'Produktname'] as $locale => $label) {
        $this->name->translateOrNew($locale)->name = $label;
    }

    $this->name->save();

    $this->family = AttributeFamily::factory()->create();
    AttributeFamily::factory()->linkAttributeGroupToFamily($this->family);
    AttributeFamily::factory()->linkAttributesToFamily($this->family, [
        $this->name->id,
        $this->color->id,
        $this->price->id,
        $this->image->id,
        $this->description->id,
        $this->shortDescription->id,
        $this->richDescription->id,
    ]);

    $this->product = Product::factory()->create([
        'attribute_family_id' => $this->family->id,
        'values'              => [
            'common' => [
                'tpl_price'       => ['EUR' => '309', 'USD' => '329'],
                'tpl_description' => 'Buy {{ tpl_name | upper }} for {{ tpl_price.EUR }}',
            ],
            'locale_specific' => [
                'en_US' => ['tpl_name' => 'Sunglasses'],
                'de_DE' => ['tpl_name' => 'Sonnenbrillen'],
            ],
        ],
    ]);
});

function templateSuggestionsUrl(Product $product, array $query = []): string
{
    return route('admin.catalog.products.template_suggestions', ['id' => $product->id, ...$query]);
}

function templatePreviewUrl(Product $product, array $query = []): string
{
    return route('admin.catalog.products.template_preview', ['id' => $product->id, ...$query]);
}

it('suggests the supported attributes of the product family except the edited attribute', function () {
    $codes = collect(getJson(templateSuggestionsUrl($this->product, ['attribute' => 'tpl_description']))
        ->assertOk()
        ->json())
        ->pluck('code');

    expect($codes)->toContain('tpl_name', 'tpl_color', 'tpl_price', 'tpl_short_description', 'tpl_rich_description')
        ->not->toContain('tpl_description', 'tpl_image', 'tpl_outsider');
});

it('suggests every supported attribute of a large family', function () {
    $codes = collect(range(1, 25))->map(fn (int $number): string => sprintf('tpl_extra_%02d', $number));

    AttributeFamily::factory()->linkAttributesToFamily(
        $this->family,
        $codes->map(fn (string $code): int => $this->variableAttribute($code)->id)->all(),
    );

    $suggested = collect(getJson(templateSuggestionsUrl($this->product, ['attribute' => 'tpl_description']))
        ->assertOk()
        ->json())
        ->pluck('code');

    expect($suggested)->toHaveCount(30)
        ->toContain(...$codes->all());
});

it('caps the suggestions of a very large family', function () {
    AttributeFamily::factory()->linkAttributesToFamily(
        $this->family,
        collect(range(1, ProductTemplateController::SUGGESTION_LIMIT))
            ->map(fn (int $number): int => $this->variableAttribute(sprintf('tpl_extra_%02d', $number))->id)
            ->all(),
    );

    getJson(templateSuggestionsUrl($this->product))
        ->assertOk()
        ->assertJsonCount(ProductTemplateController::SUGGESTION_LIMIT);
});

it('looks up one attribute of the product family by its exact code', function () {
    AttributeFamily::factory()->linkAttributesToFamily($this->family, [$this->variableAttribute('tpl_price_note')->id]);

    $types = fn (string $code): array => collect(getJson(templateSuggestionsUrl($this->product, ['code' => $code]))
        ->assertOk()
        ->json())
        ->pluck('type', 'code')
        ->all();

    expect($types('tpl_price'))->toBe(['tpl_price' => 'price'])
        ->and($types('tpl_outsider'))->toBe([]);
});

it('returns the code, translated name and type of every suggestion', function () {
    getJson(templateSuggestionsUrl($this->product, ['query' => 'tpl_name', 'locale' => 'de_DE']))
        ->assertOk()
        ->assertExactJson([
            ['code' => 'tpl_name', 'name' => 'Produktname', 'type' => 'text'],
        ]);
});

it('falls back to the attribute code when the attribute has no name in the locale', function () {
    getJson(templateSuggestionsUrl($this->product, ['query' => 'tpl_color']))
        ->assertOk()
        ->assertJsonPath('0.name', '[tpl_color]');
});

it('searches suggestions by code and by translated name', function (string $search) {
    $codes = collect(getJson(templateSuggestionsUrl($this->product, ['query' => $search, 'locale' => 'en_US']))
        ->assertOk()
        ->json())
        ->pluck('code')
        ->all();

    expect($codes)->toBe(['tpl_name']);
})->with([
    'code'       => 'tpl_na',
    'name'       => 'Product Name',
    'lower name' => 'product name',
]);

it('validates the suggestions request', function (array $query, string $error) {
    getJson(templateSuggestionsUrl($this->product, $query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$error]);
})->with([
    'query array'        => [['query' => ['tpl']], 'query'],
    'code array'         => [['code' => ['tpl_name']], 'code'],
    'attribute array'    => [['attribute' => ['tpl_description']], 'attribute'],
    'query too long'     => [['query' => str_repeat('a', 256)], 'query'],
    'code too long'      => [['code' => str_repeat('a', 256)], 'code'],
    'attribute too long' => [['attribute' => str_repeat('a', 256)], 'attribute'],
]);

it('returns not found for suggestions of an unknown product', function () {
    getJson(route('admin.catalog.products.template_suggestions', ['id' => 999999]))
        ->assertNotFound();
});

it('previews the saved template of the product', function () {
    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_description',
    ])
        ->assertOk()
        ->assertExactJson([
            'attribute' => 'tpl_description',
            'rich_text' => false,
            'source'    => 'Buy {{ tpl_name | upper }} for {{ tpl_price.EUR }}',
            'output'    => 'Buy SUNGLASSES for 309 EUR',
            'errors'    => [],
            'warnings'  => [],
        ]);
});

it('previews the template in the requested locale', function () {
    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'de_DE']), [
        'attribute' => 'tpl_description',
    ])
        ->assertOk()
        ->assertJsonPath('output', 'Buy SONNENBRILLEN for 309 EUR');
});

it('previews unsaved form values on top of the saved values', function () {
    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_description',
        'values'    => [
            'common'          => ['tpl_description' => '{{ tpl_name }} costs {{ tpl_price.USD }}'],
            'locale_specific' => ['en_US' => ['tpl_name' => 'Reading glasses']],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('source', '{{ tpl_name }} costs {{ tpl_price.USD }}')
        ->assertJsonPath('output', 'Reading glasses costs 329 USD');

    expect($this->product->fresh()->values['common']['tpl_description'])->toBe('Buy {{ tpl_name | upper }} for {{ tpl_price.EUR }}');
});

it('warns about variables without a value', function () {
    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_description',
        'values'    => ['common' => ['tpl_description' => 'Color: {{ tpl_color }}']],
    ])
        ->assertOk()
        ->assertJsonPath('output', 'Color: ')
        ->assertJsonPath('errors', [])
        ->assertJsonPath('warnings', [
            trans('product::app.templates.messages.missing-value', ['attribute' => 'tpl_color']),
        ]);
});

it('reports template errors once and not again as warnings', function () {
    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_description',
        'values'    => ['common' => ['tpl_description' => 'About {{ tpl_unknown }}']],
    ])
        ->assertOk()
        ->assertJsonPath('errors', [
            trans('product::app.templates.messages.unknown-attribute', ['attribute' => 'tpl_unknown']),
        ])
        ->assertJsonPath('warnings', []);
});

it('keeps missing value warnings next to template errors', function () {
    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_description',
        'values'    => ['common' => ['tpl_description' => '{{ tpl_color }} {{ tpl_unknown }}']],
    ])
        ->assertOk()
        ->assertJsonPath('errors', [
            trans('product::app.templates.messages.unknown-attribute', ['attribute' => 'tpl_unknown']),
        ])
        ->assertJsonPath('warnings', [
            trans('product::app.templates.messages.missing-value', ['attribute' => 'tpl_color']),
        ]);
});

it('reports a self reference only as an error', function () {
    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_description',
        'values'    => ['common' => ['tpl_description' => 'About {{ tpl_description }}']],
    ])
        ->assertOk()
        ->assertJsonPath('errors', [
            trans('product::app.templates.messages.self-reference', ['attribute' => 'tpl_description']),
        ])
        ->assertJsonPath('warnings', []);
});

it('keeps single brace warnings next to the rendered output', function () {
    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_description',
        'values'    => ['common' => ['tpl_description' => '{tpl_name} {{ tpl_name | lower }}']],
    ])
        ->assertOk()
        ->assertJsonPath('output', '{tpl_name} sunglasses')
        ->assertJsonPath('errors', [])
        ->assertJsonPath('warnings', [
            trans('product::app.templates.messages.single-brace', ['text' => 'tpl_name']),
        ]);
});

it('reports variables outside the product family', function () {
    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_description',
        'values'    => ['common' => ['tpl_description' => 'About {{ tpl_outsider }}']],
    ])
        ->assertOk()
        ->assertJsonPath('errors', [
            trans('product::app.templates.messages.not-in-family', ['attribute' => 'tpl_outsider']),
        ]);
});

it('reports circular references between unsaved templates as errors', function () {
    $message = trans('product::app.templates.messages.circular-reference', [
        'path' => 'tpl_description → tpl_short_description → tpl_description',
    ]);

    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_description',
        'values'    => ['common' => [
            'tpl_description'       => 'About {{ tpl_short_description }}',
            'tpl_short_description' => 'See {{ tpl_description }}',
        ]],
    ])
        ->assertOk()
        ->assertJsonPath('errors', [$message])
        ->assertJsonMissing(['warnings' => [$message]]);
});

it('escapes inserted values in rich text previews', function () {
    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_rich_description',
        'values'    => [
            'common'          => ['tpl_rich_description' => '<p>{{ tpl_name }}</p>'],
            'locale_specific' => ['en_US' => ['tpl_name' => '<script>alert(1)</script>']],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('rich_text', true)
        ->assertJsonPath('output', '<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>');
});

it('previews the values a variant inherits from its parent', function () {
    $parent = Product::factory()->configurable()->create([
        'attribute_family_id' => $this->family->id,
        'values'              => ['common' => ['tpl_description' => 'Parent {{ tpl_name }}']],
    ]);

    $variant = Product::factory()->create([
        'attribute_family_id' => $this->family->id,
        'parent_id'           => $parent->id,
        'values'              => ['locale_specific' => ['en_US' => ['tpl_name' => 'Variant']]],
    ]);

    postJson(templatePreviewUrl($variant, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => 'tpl_description',
    ])
        ->assertOk()
        ->assertJsonPath('output', 'Parent Variant');
});

it('validates the preview request', function (array $payload, string $error) {
    postJson(templatePreviewUrl($this->product), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$error]);
})->with([
    'missing attribute'  => [[], 'attribute'],
    'unknown attribute'  => [['attribute' => 'tpl_missing'], 'attribute'],
    'invalid values'     => [['attribute' => 'tpl_description', 'values' => 'text'], 'values'],
    'invalid section'    => [['attribute' => 'tpl_description', 'values' => ['common' => 'text']], 'values.common'],
    'too long attribute' => [['attribute' => str_repeat('a', 256)], 'attribute'],
]);

it('rejects previews of attributes that cannot hold a template in the product family', function (string $attribute) {
    $this->templateAttribute('tpl_foreign_description');

    postJson(templatePreviewUrl($this->product, ['channel' => $this->channel->code, 'locale' => 'en_US']), [
        'attribute' => $attribute,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['attribute' => trans('validation.exists', ['attribute' => 'attribute'])]);
})->with([
    'templated attribute outside the family' => 'tpl_foreign_description',
    'family attribute without templates'     => 'tpl_name',
]);

it('returns not found for the preview of an unknown product', function () {
    postJson(route('admin.catalog.products.template_preview', ['id' => 999999]), ['attribute' => 'tpl_description'])
        ->assertNotFound();
});

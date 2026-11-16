<?php

use Illuminate\Support\Facades\Blade;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Templates\Filters\FilterRegistry;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

uses(InteractsWithTemplates::class);

beforeEach(function () {
    $this->loginAsAdmin();

    $this->plain = $this->templateAttribute('tpl_plain');
    $this->rich = $this->templateAttribute('tpl_rich', ['enable_wysiwyg' => 1]);
    $this->text = $this->variableAttribute('tpl_text');
});

function templateAssistCodes(string $html): array
{
    preg_match_all('/<v-product-template-assist[^>]*\sattribute="([^"]+)"/', $html, $matches);

    return $matches[1];
}

function templateAssistProp(string $html, string $prop): array
{
    preg_match_all('/<v-product-template-assist[^>]*\s:'.$prop.'=\'([^\']*)\'/', $html, $matches);

    return array_map(fn (string $json): array => json_decode($json, true), $matches[1]);
}

function renderTemplateAssistFields($test, array $props = []): string
{
    return Blade::render(
        '<x-admin::products.dynamic-attribute-fields :fields="$fields" :locked-fields="$lockedFields" :channel-currencies="$channelCurrencies" :template-product-id="$templateProductId" fieldsWrapper="values" />',
        [
            'fields'            => collect([$test->plain, $test->rich, $test->text]),
            'lockedFields'      => [],
            'channelCurrencies' => [],
            'templateProductId' => 7,
            ...$props,
        ],
    );
}

it('adds the template assist only to plain text fields that can hold templates', function () {
    $html = renderTemplateAssistFields($this);

    expect(templateAssistCodes($html))->toBe(['tpl_plain'])
        ->and($html)->toContain(route('admin.catalog.products.template_suggestions', ['id' => 7]))
        ->and($html)->toContain('field-name="values[common][tpl_plain]"');
});

it('passes the channel currencies to the template assist', function () {
    $channel = $this->templateChannel(['EUR', 'USD'], ['en_US']);

    $html = renderTemplateAssistFields($this, ['channelCurrencies' => $channel->currencies]);

    expect(templateAssistProp($html, 'currencies'))->toHaveCount(1)
        ->and(templateAssistProp($html, 'currencies')[0])->toEqualCanonicalizing([
            ['code' => 'EUR', 'symbol' => core()->currencySymbol('EUR')],
            ['code' => 'USD', 'symbol' => core()->currencySymbol('USD')],
        ]);
});

it('passes the template filters to the template assist', function () {
    $filters = templateAssistProp(renderTemplateAssistFields($this), 'filters');

    expect($filters)->toHaveCount(1)
        ->and($filters[0])->toBe(app(FilterRegistry::class)->suggestions());
});

it('does not add the template assist without a product', function () {
    expect(templateAssistCodes(renderTemplateAssistFields($this, ['templateProductId' => null])))->toBe([]);
});

it('does not add the template assist to locked variant fields', function () {
    $html = renderTemplateAssistFields($this, [
        'lockedFields' => ['tpl_plain' => ['level' => 'common', 'value' => 'Inherited']],
    ]);

    expect(templateAssistCodes($html))->toBe([]);
});

it('adds the template assist to the product edit page and its lazy attribute groups', function () {
    $family = AttributeFamily::factory()->create();
    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, [$this->plain->id, $this->rich->id, $this->text->id]);

    $product = Product::factory()->simple()->create(['attribute_family_id' => $family->id]);

    $page = get(route('admin.catalog.products.edit', $product->id))->assertOk()->getContent();

    expect(templateAssistCodes($page))->toBe(['tpl_plain'])
        ->and($page)->toMatch('/v-pre\s*>\{\{ name \| upper \}\}<\/code>/')
        ->and($page)->toMatch('/v-pre\s*>\{\{ price\.EUR \}\}<\/code>/');

    $groupId = $family->familyGroups()->value('attribute_groups.id');

    $html = getJson(route('admin.catalog.products.attribute_group_fields', ['id' => $product->id, 'groupId' => $groupId]))
        ->assertOk()
        ->json('html');

    expect(templateAssistCodes($html))->toBe(['tpl_plain'])
        ->and($html)->toContain("app.component('v-product-template-assist'");
});

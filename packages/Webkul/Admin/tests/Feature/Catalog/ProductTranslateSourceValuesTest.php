<?php

use Webkul\Core\Models\Channel;
use Webkul\Product\Models\Product;

use function Pest\Laravel\get;

it('returns the translatable attributes with their source values for the requested scope', function () {
    $this->loginAsAdmin();

    $channel = Channel::first();
    $locale = $channel->locales()->first();

    $product = Product::factory()->create(['type' => 'simple']);

    $attribute = $product->getEditableAttributes()->firstWhere('code', 'name');

    $attribute->forceFill(['ai_translate' => 1])->save();

    $product->forceFill([
        'values' => [
            'channel_locale_specific' => [
                $channel->code => [
                    $locale->code => ['name' => 'Corallae Paeonia Tee'],
                ],
            ],
        ],
    ])->save();

    $response = get(route('admin.catalog.product.get_attribute', [
        'productId' => $product->id,
        'channel'   => $channel->code,
        'locale'    => $locale->code,
    ]), ['Accept' => 'application/json'])->assertOk();

    expect(collect($response->json('attributes'))->pluck('id'))->toContain('name')
        ->and($response->json('values.name'))->toBe('Corallae Paeonia Tee');
});

it('rejects a scope that does not exist', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create(['type' => 'simple']);

    get(route('admin.catalog.product.get_attribute', [
        'productId' => $product->id,
        'channel'   => 'not-a-channel',
    ]), ['Accept' => 'application/json'])->assertStatus(422);
});

it('shows the source preview and keeps the translate modal on theme tokens', function () {
    $source = file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/catalog/products/edit/more-actions/translate-action.blade.php')
    );

    expect($source)
        ->toContain('translate.attributes-to-translate')
        ->toContain('translate.overwrite-warning')
        ->toContain('previewValue(attribute.id)')
        ->toContain('goBackToStep1')
        ->not->toContain('#6d28d9');
});

it('keeps both translate modals on the same source preview and platform contract', function () {
    $bulk = file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/catalog/products/edit/more-actions/translate-action.blade.php')
    );

    $field = file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/catalog/products/edit/fields/translate-button.blade.php')
    );

    expect($bulk)->toContain('translate.attributes-to-translate')
        ->and($bulk)->toContain('previewValue(attribute.id)')
        ->and($field)->toContain('sourcePreview || noValueLabel')
        ->and($field)->not->toContain('translate.attributes-to-translate');

    foreach ([$bulk, $field] as $source) {
        expect($source)
            ->toContain('translate.overwrite-warning')
            ->toContain('goBackToStep1')
            ->toContain('name="platform_id"')
            ->toContain('ai-generation.platform')
            ->toContain('ai-generation.model')
            ->toContain("formData.set('platform_id'")
            ->not->toContain('#6d28d9')
            ->not->toContain('max-w-lg');
    }
});

it('returns the source value of a locale only attribute', function () {
    $this->loginAsAdmin();

    $channel = Channel::first();
    $locale = $channel->locales()->first();

    $product = Product::factory()->create(['type' => 'simple']);

    $attribute = $product->getEditableAttributes()->firstWhere('code', 'name');

    $attribute->forceFill(['ai_translate' => 1, 'value_per_locale' => 1, 'value_per_channel' => 0])->save();

    $product->forceFill([
        'values' => [
            'locale_specific' => [
                $locale->code => ['name' => 'Corallae Paeonia Tee'],
            ],
        ],
    ])->save();

    $response = get(route('admin.catalog.product.get_attribute', [
        'productId' => $product->id,
        'channel'   => $channel->code,
        'locale'    => $locale->code,
    ]), ['Accept' => 'application/json'])->assertOk();

    expect($response->json('values.name'))->toBe('Corallae Paeonia Tee');
});

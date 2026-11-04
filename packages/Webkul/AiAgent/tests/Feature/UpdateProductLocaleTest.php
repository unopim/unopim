<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\UpdateProduct;
use Webkul\AiAgent\Jobs\TranslateProductValuesJob;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * Runs `update_product` against a real product to prove where locale-scoped
 * values land: the explicit `locale` argument, the conversation locale as
 * fallback, and locale maps, each checked against the active locales.
 */
beforeEach(function () {
    Queue::fake();

    Locale::whereIn('code', ['en_US', 'de_DE'])->update(['status' => 1]);

    $this->inactiveLocale = Locale::where('status', 0)->value('code');

    $this->product = Product::factory()->simple()->create();

    $this->localeAttribute = Attribute::factory()->create([
        'type'              => 'textarea',
        'value_per_locale'  => true,
        'value_per_channel' => false,
    ]);

    $familyGroupId = DB::table('attribute_family_group_mappings')
        ->where('attribute_family_id', $this->product->attribute_family_id)
        ->value('id');

    DB::table('attribute_group_mappings')->insert([
        'attribute_id'              => $this->localeAttribute->id,
        'attribute_family_group_id' => $familyGroupId,
        'position'                  => 999,
    ]);

    $this->channelLocaleAttribute = Attribute::factory()->create([
        'type'              => 'textarea',
        'value_per_locale'  => true,
        'value_per_channel' => true,
    ]);

    DB::table('attribute_group_mappings')->insert([
        'attribute_id'              => $this->channelLocaleAttribute->id,
        'attribute_family_group_id' => $familyGroupId,
        'position'                  => 1000,
    ]);
});

function updateProductLocaleChatContext(string $locale = 'en_US'): ChatContext
{
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['catalog', 'catalog.products', 'catalog.products.edit'],
    ]);

    return new ChatContext(
        message: 'Update product',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: $locale,
        channel: 'default',
        platform: new MagicAIPlatform([
            'provider' => 'openai',
            'models'   => 'gpt-4o',
        ]),
        model: 'gpt-4o',
        uploadedImagePaths: [],
        uploadedFilePaths: [],
        currentPage: null,
        user: Admin::factory()->create([
            'password' => Hash::make('password'),
            'role_id'  => $role->id,
        ]),
    );
}

function runUpdateProduct(ChatContext $context, array $arguments): array
{
    return json_decode(
        app(UpdateProduct::class)->register($context)->handle(new Request($arguments)),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
}

it('writes a per-locale value to the explicitly requested locale and leaves en_US alone', function () {
    $code = $this->localeAttribute->code;

    $this->product->forceFill(['values' => ['locale_specific' => ['en_US' => [$code => 'English copy']]]])->save();

    $result = runUpdateProduct(updateProductLocaleChatContext(), [
        'sku'          => $this->product->sku,
        'changes_json' => json_encode([$code => 'Deutscher Text']),
        'locale'       => 'de_DE',
    ]);

    $values = $this->product->fresh()->values;

    expect($result['result']['updated'])->toBe(1)
        ->and($values['locale_specific']['de_DE'][$code])->toBe('Deutscher Text')
        ->and($values['locale_specific']['en_US'][$code])->toBe('English copy');
});

it('falls back to the conversation locale and queues translation when no locale is given', function () {
    $code = $this->localeAttribute->code;

    runUpdateProduct(updateProductLocaleChatContext('de_DE'), [
        'sku'          => $this->product->sku,
        'changes_json' => json_encode([$code => 'Deutscher Text']),
    ]);

    expect($this->product->fresh()->values['locale_specific']['de_DE'][$code])->toBe('Deutscher Text');

    Queue::assertPushed(TranslateProductValuesJob::class);
});

it('does not queue translation when a locale is named explicitly', function () {
    runUpdateProduct(updateProductLocaleChatContext(), [
        'sku'          => $this->product->sku,
        'changes_json' => json_encode([$this->localeAttribute->code => 'Deutscher Text']),
        'locale'       => 'de_DE',
    ]);

    Queue::assertNotPushed(TranslateProductValuesJob::class);
});

it('rejects an inactive explicit locale and leaves the product unchanged', function () {
    $before = $this->product->fresh()->values;

    $result = runUpdateProduct(updateProductLocaleChatContext(), [
        'sku'          => $this->product->sku,
        'changes_json' => json_encode([$this->localeAttribute->code => 'Text']),
        'locale'       => $this->inactiveLocale,
    ]);

    expect($result['error'])->toContain($this->inactiveLocale)
        ->and($result['active_locales'])->toContain('en_US', 'de_DE')
        ->and($this->product->fresh()->values)->toEqual($before);
});

it('rejects an inactive conversation locale from the page instead of writing to it', function () {
    $before = $this->product->fresh()->values;

    $result = runUpdateProduct(updateProductLocaleChatContext($this->inactiveLocale), [
        'sku'          => $this->product->sku,
        'changes_json' => json_encode([$this->localeAttribute->code => 'Text']),
    ]);

    expect($result['error'])->toContain($this->inactiveLocale)
        ->and($result['active_locales'])->toContain('en_US', 'de_DE')
        ->and($this->product->fresh()->values)->toEqual($before);
});

it('writes every entry of a locale map whose keys are all active locales', function () {
    $code = $this->localeAttribute->code;

    runUpdateProduct(updateProductLocaleChatContext(), [
        'sku'          => $this->product->sku,
        'changes_json' => json_encode([$code => ['en_US' => 'English', 'de_DE' => 'Deutsch']]),
    ]);

    $values = $this->product->fresh()->values;

    expect($values['locale_specific']['en_US'][$code])->toBe('English')
        ->and($values['locale_specific']['de_DE'][$code])->toBe('Deutsch');
});

it('rejects a locale map with an unknown key, names the key and writes nothing', function () {
    $code = $this->localeAttribute->code;

    $result = runUpdateProduct(updateProductLocaleChatContext(), [
        'sku'          => $this->product->sku,
        'changes_json' => json_encode([$code => ['de_DE' => 'x', 'typo' => 'y']]),
    ]);

    expect($result['result']['errors'])->toHaveCount(1)
        ->and($result['result']['errors'][0])->toContain('typo')
        ->and($result['result']['errors'][0])->toContain('active locales')
        ->and(data_get($this->product->fresh()->values, "locale_specific.de_DE.{$code}"))->toBeNull();
});

it('writes a channel and locale attribute under the requested locale of every channel', function () {
    $code = $this->channelLocaleAttribute->code;

    runUpdateProduct(updateProductLocaleChatContext(), [
        'sku'          => $this->product->sku,
        'changes_json' => json_encode([$code => 'Deutscher Text']),
        'locale'       => 'de_DE',
    ]);

    $values = $this->product->fresh()->values;

    foreach (Channel::pluck('code') as $channel) {
        expect($values['channel_locale_specific'][$channel]['de_DE'][$code])->toBe('Deutscher Text')
            ->and($values['channel_locale_specific'][$channel]['en_US'][$code] ?? null)->toBeNull();
    }
});

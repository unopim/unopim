<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Webkul\MagicAI\Jobs\SaveTranslatedAllAttributesJob;
use Webkul\MagicAI\Jobs\SaveTranslatedDataJob;
use Webkul\Product\Models\Product;

function appliedRollbackChangeset(Product $product): int
{
    return DB::table('ai_agent_changesets')->insertGetId([
        'user_id'        => null,
        'description'    => 'AI edit',
        'changes'        => json_encode([
            'product_id'      => $product->id,
            'previous_values' => ['common' => ['sku' => 'rolled-back-sku']],
        ]),
        'status'         => 'applied',
        'affected_count' => 1,
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
}

it('denies rolling back an AI changeset without the product edit permission', function () {
    $product = Product::factory()->simple()->create();
    $changesetId = appliedRollbackChangeset($product);

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'ai-agent.dashboard']);

    $this->postJson(route('ai-agent.dashboard.rollback', $changesetId))->assertForbidden();

    expect(DB::table('ai_agent_changesets')->where('id', $changesetId)->value('status'))->toBe('applied');
});

it('allows rolling back an AI changeset with the product edit permission', function () {
    $product = Product::factory()->simple()->create();
    $changesetId = appliedRollbackChangeset($product);

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'ai-agent.dashboard', 'catalog', 'catalog.products', 'catalog.products.edit']);

    $this->postJson(route('ai-agent.dashboard.rollback', $changesetId))->assertOk();

    expect(DB::table('ai_agent_changesets')->where('id', $changesetId)->value('status'))->toBe('rolled_back');
});

it('denies saving translated product values without the product edit permission', function (string $route) {
    Bus::fake();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent']);

    $this->postJson(route($route), [
        'resource_id'    => 1,
        'translatedData' => json_encode([['locale' => 'fr_FR', 'content' => 'Bonjour']]),
        'targetChannel'  => 'default',
        'field'          => 'name',
    ])->assertForbidden();

    Bus::assertNotDispatched(SaveTranslatedDataJob::class);
    Bus::assertNotDispatched(SaveTranslatedAllAttributesJob::class);
})->with([
    'single attribute' => ['admin.magic_ai.store.translated'],
    'all attributes'   => ['admin.magic_ai.store.translated.all_attribute'],
]);

it('saves translated product values with the product edit permission', function () {
    Bus::fake();

    $this->loginWithPermissions(permissions: ['dashboard', 'ai-agent', 'catalog', 'catalog.products', 'catalog.products.edit']);

    $this->postJson(route('admin.magic_ai.store.translated'), [
        'resource_id'    => 1,
        'translatedData' => json_encode([['locale' => 'fr_FR', 'content' => 'Bonjour']]),
        'targetChannel'  => 'default',
        'field'          => 'name',
    ])->assertOk();

    Bus::assertDispatched(SaveTranslatedDataJob::class);
});

it('denies writing AI agent settings through the generic configuration route', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'configuration']);

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'magic_ai']), [
        'general' => ['magic_ai' => ['agentic_pim' => ['custom_instructions' => 'Injected install rule']]],
    ])->assertForbidden();

    $this->assertDatabaseMissing('core_config', [
        'code'  => 'general.magic_ai.agentic_pim.custom_instructions',
        'value' => 'Injected install rule',
    ]);
});

it('allows writing AI agent settings with the AI agent settings permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard', 'configuration', 'ai-agent', 'ai-agent.general']);

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'magic_ai']), [
        'general' => ['magic_ai' => ['agentic_pim' => ['custom_instructions' => 'Allowed install rule']]],
    ])->assertRedirect();

    $this->assertDatabaseHas('core_config', [
        'code'  => 'general.magic_ai.agentic_pim.custom_instructions',
        'value' => 'Allowed install rule',
    ]);
});

it('denies advancing an export job without the export execute permission', function (string $route) {
    $this->loginWithPermissions(permissions: ['dashboard', 'data_transfer', 'data_transfer.export']);

    $this->postJson(route($route, ['id' => 1]))->assertForbidden();
})->with([
    'link'  => ['admin.settings.data_transfer.exports.link'],
    'index' => ['admin.settings.data_transfer.exports.index_data'],
]);

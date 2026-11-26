<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Jobs\AutoEnrichProductJob;
use Webkul\AiAgent\Services\EnrichmentService;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function (): void {
    Http::preventStrayRequests();

    $this->actingAs(Admin::factory()->create([
        'role_id' => Role::factory()->create(['permission_type' => 'all'])->id,
    ]), 'admin');
});

/**
 * Run the auto-enrich job against a fake AI platform that returns the given fields.
 *
 * @param  array<string, string>  $generated
 */
function runAutoEnrich(Product $product, array $generated): void
{
    $platformRepository = Mockery::mock(MagicAIPlatformRepository::class);
    $platformRepository->shouldReceive('getActiveDefault')->andReturn((object) [
        'id'         => 1,
        'label'      => 'Fake',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => 'sk-test',
        'model_list' => ['gpt-4o-mini'],
    ]);

    $enrichmentService = Mockery::mock(EnrichmentService::class);
    $enrichmentService->shouldReceive('enrich')->once()->andReturn(new ImageProductContext(enrichment: $generated));

    (new AutoEnrichProductJob($product->id, 'en_US', 'default'))->handle($enrichmentService, $platformRepository);
}

/**
 * Read the product's stored values straight from the database.
 */
function storedProductValues(int $productId): array
{
    return json_decode((string) DB::table('products')->where('id', $productId)->value('values'), true);
}

it('stores the values from before the change in the auto-enrich changeset', function (): void {
    $product = Product::factory()->simple()->withInitialValues()->create();

    runAutoEnrich($product, ['name' => 'AI name', 'description' => '<p>AI description</p>']);

    $changes = json_decode((string) DB::table('ai_agent_changesets')->latest('id')->value('changes'), true);

    expect($changes['product_id'])->toBe($product->id)
        ->and($changes['previous_values'])->toEqual([
            'channel' => 'default',
            'locale'  => 'en_US',
            'values'  => ['name' => null, 'description' => null],
        ]);
});

it('restores the product values when the auto-enrich changeset is rolled back', function (): void {
    $product = Product::factory()->simple()->withInitialValues()->create();

    $before = storedProductValues($product->id);

    runAutoEnrich($product, ['name' => 'AI name', 'description' => '<p>AI description</p>']);

    expect(storedProductValues($product->id)['channel_locale_specific']['default']['en_US']['name'])->toBe('AI name');

    $changesetId = DB::table('ai_agent_changesets')->latest('id')->value('id');

    $this->postJson(route('ai-agent.dashboard.rollback', $changesetId))
        ->assertOk()
        ->assertJsonPath('affected', 1);

    expect(storedProductValues($product->id))->toBe($before)
        ->and(DB::table('ai_agent_changesets')->where('id', $changesetId)->value('status'))->toBe('rolled_back');
});

/**
 * Roll back the latest auto-enrich changeset through the dashboard route.
 */
function rollBackLatestAutoEnrich(): void
{
    test()->postJson(route('ai-agent.dashboard.rollback', DB::table('ai_agent_changesets')->latest('id')->value('id')))
        ->assertOk()
        ->assertJsonPath('affected', 1);
}

it('keeps edits made after the enrichment when the changeset is rolled back', function (): void {
    $product = Product::factory()->simple()->withInitialValues()->create();

    runAutoEnrich($product, ['name' => 'AI name', 'description' => '<p>AI description</p>']);

    $values = storedProductValues($product->id);
    $values['common']['color'] = 'red';
    $values['channel_locale_specific']['default']['fr_FR']['name'] = 'Nom manuel';
    DB::table('products')->where('id', $product->id)->update(['values' => json_encode($values)]);

    rollBackLatestAutoEnrich();

    $after = storedProductValues($product->id);

    expect($after['common']['color'])->toBe('red')
        ->and($after['channel_locale_specific']['default']['fr_FR']['name'])->toBe('Nom manuel')
        ->and($after['channel_locale_specific']['default'])->not->toHaveKey('en_US');
});

it('keeps the existing name when only the description was generated', function (): void {
    $product = Product::factory()->simple()->withInitialValues()->create();

    $values = storedProductValues($product->id);
    $values['channel_locale_specific']['default']['en_US']['name'] = 'Existing name';
    DB::table('products')->where('id', $product->id)->update(['values' => json_encode($values)]);

    runAutoEnrich($product, ['description' => '<p>AI description</p>']);

    $changes = json_decode((string) DB::table('ai_agent_changesets')->latest('id')->value('changes'), true);

    expect($changes['previous_values']['values'])->toBe(['description' => null]);

    rollBackLatestAutoEnrich();

    expect(storedProductValues($product->id)['channel_locale_specific']['default']['en_US'])->toBe(['name' => 'Existing name']);
});

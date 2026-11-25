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

beforeEach(function () {
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

function storedProductValues(int $productId): array
{
    return json_decode((string) DB::table('products')->where('id', $productId)->value('values'), true);
}

it('stores the values from before the change in the auto-enrich changeset', function () {
    $product = Product::factory()->simple()->withInitialValues()->create();

    $before = storedProductValues($product->id);

    runAutoEnrich($product, ['name' => 'AI name', 'description' => '<p>AI description</p>']);

    $changes = json_decode((string) DB::table('ai_agent_changesets')->latest('id')->value('changes'), true);

    expect($changes['product_id'])->toBe($product->id)
        ->and($changes['previous_values'])->toBe($before);
});

it('restores the product values when the auto-enrich changeset is rolled back', function () {
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

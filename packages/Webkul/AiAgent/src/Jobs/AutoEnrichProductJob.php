<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Services\EnrichmentService;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

/**
 * Automatically enriches a product with AI-generated content
 * when it's created or imported with missing fields.
 *
 * Triggered by the auto-enrichment event listener when enabled.
 */
class AutoEnrichProductJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        protected int $productId,
        protected string $locale = 'en_US',
        protected string $channel = 'default',
    ) {
        $this->queue = 'default';
    }

    public function handle(
        EnrichmentService $enrichmentService,
        MagicAIPlatformRepository $platformRepository,
    ): void {
        // Prevent duplicate enrichment via cache lock
        $lock = Cache::lock("auto-enrich:{$this->productId}", 120);

        if (! $lock->get()) {
            return; // Already being enriched by another job
        }

        try {
            $this->enrich($enrichmentService, $platformRepository);
        } finally {
            $lock->release();
        }
    }

    protected function enrich(
        EnrichmentService $enrichmentService,
        MagicAIPlatformRepository $platformRepository,
    ): void {
        $product = DB::table('products')->where('id', $this->productId)->first();

        if (! $product) {
            return;
        }

        $values = json_decode($product->values, true) ?? [];
        $common = $values['common'] ?? [];
        $cl = $values['channel_locale_specific'][$this->channel][$this->locale] ?? [];

        // Only enrich if key fields are missing
        $hasName = ! empty($cl['name'] ?? $common['name'] ?? null);
        $hasDescription = ! empty($cl['description'] ?? $common['description'] ?? null);

        if ($hasName && $hasDescription) {
            return; // Product already has core content
        }

        // Resolve AI platform
        $platform = $platformRepository->getDefault() ?? $platformRepository->getActiveList()->first();

        if (! $platform) {
            Log::warning('AutoEnrichProductJob: No AI platform configured', ['sku' => $product->sku]);

            return;
        }

        try {
            $aiProvider = AiProvider::from($platform->provider);
            $apiClient = app(AiApiClient::class);
            $apiClient->configure(new CredentialConfig(
                id: $platform->id,
                label: $platform->label,
                provider: $platform->provider,
                apiUrl: $platform->api_url ?: $aiProvider->defaultUrl(),
                apiKey: $platform->api_key,
                model: $platform->model_list[0] ?? 'gpt-4o',
            ));

            $existing = array_merge($common, $cl);
            $ctx = new ImageProductContext(
                attributes: $existing,
                detectedProduct: $common['product_type'] ?? null,
                category: $values['categories'][0] ?? null,
            );

            $enriched = $enrichmentService->enrich(
                ctx: $ctx,
                credentialId: 0,
                apiClient: $apiClient,
                options: ['locale' => $this->locale],
            );

            $generated = $enriched->enrichment;

            if (empty($generated)) {
                return;
            }

            // Apply generated content
            $productValues = json_decode($product->values, true) ?? [];
            foreach ($generated as $key => $value) {
                $productValues['channel_locale_specific'][$this->channel][$this->locale][$key] = $value;
            }

            $repo = app('Webkul\Product\Repositories\ProductRepository');
            $repo->updateWithValues(['values' => $productValues], $product->id);

            // Record what was auto-generated
            DB::table('ai_agent_changesets')->insert([
                'user_id'        => null,
                'description'    => "Auto-enriched product {$product->sku}: ".implode(', ', array_keys($generated)),
                'changes'        => json_encode([
                    'product_id' => $product->id,
                    'sku'        => $product->sku,
                    'generated'  => $generated,
                ]),
                'status'         => 'applied',
                'affected_count' => 1,
                'applied_at'     => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            Log::info('AutoEnrichProductJob: Enriched product', ['sku' => $product->sku, 'fields' => array_keys($generated)]);
        } catch (\Throwable $e) {
            Log::error('AutoEnrichProductJob failed', ['sku' => $product->sku, 'error' => $e->getMessage()]);
        }
    }
}

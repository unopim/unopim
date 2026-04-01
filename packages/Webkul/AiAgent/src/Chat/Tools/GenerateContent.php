<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Services\EnrichmentService;
use Webkul\MagicAI\Enums\AiProvider;

class GenerateContent implements PimTool
{
    use ChecksPermission;

    public function __construct(
        protected EnrichmentService $enrichmentService,
    ) {}

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('generate_content')
            ->for('Generate product name, description, and SEO content for a product.')
            ->withStringParameter('sku', 'Product SKU to generate content for')
            ->withStringParameter('instruction', 'Optional instructions for content generation style or focus')
            ->using(function (string $sku, ?string $instruction = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products.edit')) {
                    return $denied;
                }

                $product = DB::table('products')->where('sku', $sku)->first();

                if (! $product) {
                    return json_encode(['error' => "Product not found: {$sku}"]);
                }

                $values = json_decode($product->values, true) ?? [];
                $common = $values['common'] ?? [];
                $channelLocale = $values['channel_locale_specific'][$context->channel][$context->locale] ?? [];

                // Build existing attributes for enrichment context
                $existing = array_merge($common, $channelLocale);

                $ctx = new ImageProductContext(
                    attributes: $existing,
                    detectedProduct: $common['product_type'] ?? null,
                    category: ($values['categories'][0] ?? null),
                );

                try {
                    // Configure API client with the platform from this chat session
                    $apiClient = app(AiApiClient::class);
                    $apiClient->configure(new CredentialConfig(
                        id: $context->platform->id,
                        label: $context->platform->label,
                        provider: $context->platform->provider,
                        apiUrl: $context->platform->api_url ?: AiProvider::from($context->platform->provider)->defaultUrl(),
                        apiKey: $context->platform->api_key,
                        model: $context->model,
                    ));

                    $enriched = $this->enrichmentService->enrich(
                        ctx: $ctx,
                        credentialId: 0,
                        apiClient: $apiClient,
                        options: [
                            'locale'      => $context->locale,
                            'instruction' => $instruction ?? '',
                        ],
                    );

                    $generated = $enriched->enrichment;

                    if (empty($generated)) {
                        return json_encode(['info' => 'All content fields are already filled.']);
                    }

                    // Auto-apply the generated content to the product
                    $productValues = json_decode($product->values, true) ?? [];

                    foreach ($generated as $key => $value) {
                        $productValues['channel_locale_specific'][$context->channel][$context->locale][$key] = $value;
                    }

                    $repo = app('Webkul\Product\Repositories\ProductRepository');
                    $repo->updateWithValues(['values' => $productValues], $product->id);

                    return json_encode([
                        'result' => [
                            'generated' => array_keys($generated),
                            'sku'       => $sku,
                            'content'   => $generated,
                        ],
                        'product_url' => route('admin.catalog.products.edit', $product->id),
                    ]);
                } catch (\Throwable $e) {
                    return json_encode(['error' => 'Content generation failed: '.$e->getMessage()]);
                }
            });
    }
}

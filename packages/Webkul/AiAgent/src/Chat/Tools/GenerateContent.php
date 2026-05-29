<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Services\EnrichmentService;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\Product\Repositories\ProductRepository;

class GenerateContent implements PimTool
{
    public function __construct(
        protected EnrichmentService $enrichmentService,
    ) {}

    public function register(ChatContext $context): Tool
    {
        $enrichmentService = $this->enrichmentService;

        return new class($context, $enrichmentService) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(ChatContext $context, protected EnrichmentService $enrichmentService)
            {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'generate_content';
            }

            public function description(): string
            {
                return 'Generate product name, description, and SEO content for a product.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'sku'         => $schema->string()->description('Product SKU to generate content for'),
                    'instruction' => $schema->string()->description('Optional instructions for content generation style or focus'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products.edit')) {
                    return $denied;
                }

                $sku = $request->string('sku')->toString();
                $instruction = $request->string('instruction')->toString() ?: null;

                $product = DB::table('products')->where('sku', $sku)->first();

                if (! $product) {
                    return json_encode(['error' => "Product not found: {$sku}"]);
                }

                $values = json_decode((string) $product->values, true) ?? [];
                $common = $values['common'] ?? [];
                $channelLocale = $values['channel_locale_specific'][$this->context->channel][$this->context->locale] ?? [];

                // Use only the locale-specific bucket for the completeness check so that
                // content present in another locale (e.g. en_US) does not falsely mark
                // this locale as already filled.
                $ctx = new ImageProductContext(
                    detectedProduct: $common['product_type'] ?? null,
                    attributes: $channelLocale,
                    category: ($values['categories'][0] ?? null),
                );

                try {
                    // Configure API client with the platform from this chat session
                    $apiClient = app(AiApiClient::class);
                    $apiClient->configure(new CredentialConfig(
                        id: $this->context->platform->id,
                        label: $this->context->platform->label,
                        provider: $this->context->platform->provider,
                        apiUrl: $this->context->platform->api_url ?: AiProvider::from($this->context->platform->provider)->defaultUrl(),
                        apiKey: $this->context->platform->api_key,
                        model: $this->context->model,
                    ));

                    $enriched = $this->enrichmentService->enrich(
                        ctx: $ctx,
                        credentialId: 0,
                        apiClient: $apiClient,
                        options: [
                            'locale'      => $this->context->locale,
                            'instruction' => $instruction ?? '',
                            'common'      => $common,
                        ],
                    );

                    $generated = $enriched->enrichment;

                    if ($generated === []) {
                        return json_encode(['info' => 'All content fields are already filled.']);
                    }

                    // Auto-apply the generated content to the product
                    $productValues = json_decode((string) $product->values, true) ?? [];

                    foreach ($generated as $key => $value) {
                        $productValues['channel_locale_specific'][$this->context->channel][$this->context->locale][$key] = $value;
                    }

                    $repo = app(ProductRepository::class);
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
            }
        };
    }
}

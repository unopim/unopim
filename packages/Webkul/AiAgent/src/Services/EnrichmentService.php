<?php

namespace Webkul\AiAgent\Services;

use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Exceptions\ApiException;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Repositories\CredentialRepository;

/**
 * Enriches an ImageProductContext by generating missing product attributes
 * (name, description, SEO fields, etc.) via a second AI call.
 */
class EnrichmentService
{
    /**
     * Keys to generate if absent in the context.
     *
     * @var array<string>
     */
    protected const TARGETS = [
        'name',
        'short_description',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'product_number',
    ];

    public function __construct(
        protected AiApiClient $apiClient,
        protected CredentialRepository $credentialRepository,
    ) {}

    /**
     * Enrich the ImageProductContext with AI-generated attributes.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws ApiException
     */
    public function enrich(ImageProductContext $ctx, int $credentialId = 0, ?AiApiClient $apiClient = null, array $options = []): ImageProductContext
    {
        $existing = array_merge($ctx->enrichment, $ctx->attributes);

        $missing = array_filter(
            self::TARGETS,
            fn (string $key) => empty($existing[$key]),
        );

        if (empty($missing)) {
            return $ctx;
        }

        // Use pre-configured client if provided
        if ($apiClient) {
            $this->apiClient = $apiClient;
        } elseif ($credentialId > 0) {
            $credential = $this->credentialRepository->findOrFail($credentialId);
            $config = CredentialConfig::fromModel($credential);
            $this->apiClient->configure($config);
        } else {
            $config = $this->buildMagicAiConfig();
            $this->apiClient->configure($config);
        }

        $locale = $options['locale'] ?? 'en';
        $instruction = $options['instruction'] ?? '';

        $messages = $this->buildMessages($existing, array_values($missing), $locale, $instruction, $ctx);

        $response = $this->apiClient->chat(messages: $messages, maxTokens: 2048, temperature: 0.6);
        $generated = $this->parseResponse($response['content'] ?? '');

        // Score: AI-generated fields get medium confidence
        $confidence = [];
        foreach ($generated as $key => $value) {
            $confidence[$key] = 0.65;
        }

        // Vision-detected attributes get high confidence
        foreach ($ctx->attributes as $key => $value) {
            if (! empty($value)) {
                $confidence[$key] = 0.90;
            }
        }

        return $ctx
            ->withEnrichment($generated)
            ->withConfidence($confidence);
    }

    /**
     * Build the AI messages for enrichment.
     *
     * @param  array<string, mixed>  $existing
     * @param  array<string>  $missing
     * @return array<int, array{role: string, content: string}>
     */
    protected function buildMessages(
        array $existing,
        array $missing,
        string $locale,
        string $instruction,
        ImageProductContext $ctx,
    ): array {
        $existingJson = json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $targetKeys = json_encode($missing);

        $extra = '';
        if (! empty($instruction)) {
            $extra = "\n\nAdditional instructions from the user:\n$instruction";
        }

        $productInfo = '';
        if ($ctx->detectedProduct) {
            $productInfo = "\nDetected product type: {$ctx->detectedProduct}";
        }
        if ($ctx->category) {
            $productInfo .= "\nSuggested category: {$ctx->category}";
        }

        return [
            [
                'role'    => 'system',
                'content' => <<<SYSTEM
                    You are a professional product content writer for an e-commerce PIM.
                    Generate only the requested attributes for the product catalog.
                    Base your response on the provided product data.
                    Target locale: $locale.
                    Return a single flat JSON object with exactly the requested keys.
                    Use concise, conversion-focused copy.
                    SYSTEM,
            ],
            [
                'role'    => 'user',
                'content' => <<<USER
                    Existing product data:
                    $existingJson
                    $productInfo
                    $extra

                    Generate values for these missing attributes: $targetKeys

                    Return ONLY a JSON object with those keys and no other text.
                    USER,
            ],
        ];
    }

    /**
     * Parse JSON from AI response.
     *
     * @return array<string, mixed>
     */
    protected function parseResponse(string $raw): array
    {
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $clean = preg_replace('/\s*```\s*$/m', '', $clean ?? $raw);
        $decoded = json_decode(trim($clean ?? ''), true);

        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
    }

    /**
     * Build a CredentialConfig from the Magic AI system configuration.
     * Used when credentialId = 0 (no custom credential specified).
     */
    protected function buildMagicAiConfig(): CredentialConfig
    {
        $platform = (string) (core()->getConfigData('general.magic_ai.settings.ai_platform') ?? 'openai');
        $apiKey = (string) (core()->getConfigData('general.magic_ai.settings.api_key') ?? '');
        $models = (string) (core()->getConfigData('general.magic_ai.settings.api_model') ?? 'gpt-4o');
        $domain = (string) (core()->getConfigData('general.magic_ai.settings.api_domain') ?? '');
        $model = trim(explode(',', $models)[0]);

        if (! $domain) {
            $domain = match ($platform) {
                'openai' => 'https://api.openai.com/v1',
                'gemini' => 'https://generativelanguage.googleapis.com',
                'groq'   => 'https://api.groq.com',
                'ollama' => 'http://localhost:11434',
                default  => 'https://api.openai.com/v1',
            };
        } elseif (! preg_match('#^https?://#i', $domain)) {
            $domain = 'https://'.$domain;
        }

        return new CredentialConfig(
            id: 0,
            label: 'Magic AI',
            provider: $platform,
            apiUrl: $domain,
            apiKey: $apiKey,
            model: $model,
        );
    }
}

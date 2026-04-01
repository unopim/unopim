<?php

namespace Webkul\AiAgent\Services;

use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Exceptions\ApiException;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Repositories\CredentialRepository;

/**
 * Provider-agnostic service for submitting images to an AI vision model
 * and returning a structured {@see ImageProductContext} DTO.
 *
 * Retry policy
 * ─────────────
 * Transient failures (network, 429, 5xx) are retried up to $maxAttempts
 * times with truncated exponential backoff + full jitter:
 *
 *   delay = random(0, min(capMs, baseMs * 2^attempt))
 *
 * The default cap is 8 s; each attempt doubles the ceiling.
 * Non-retriable errors (4xx except 429, validation) propagate immediately.
 *
 * Provider support
 * ─────────────────
 * Message structure is assembled per-provider inside buildVisionMessages():
 *   • openai    — GPT-4o / GPT-4 Vision  (multi-modal content array)
 *   • anthropic — Claude 3+ Vision       (source block per spec)
 *   • generic   — URL-in-text fallback for any other provider
 */
class VisionService
{
    /**
     * Default system prompt sent with every vision request.
     */
    protected const DEFAULT_SYSTEM_PROMPT = <<<'PROMPT'
        You are an expert product vision analyst for an e-commerce PIM system.
        Analyze the provided product image and return a JSON object with these fields:

        {
          "productType":         string,
          "productName":         string,
          "detectedObjects":     string[],
          "colors":              string[],
          "sizes":               string[],
          "materials":           string[],
          "patterns":            string[],
          "style":               string,
          "condition":           "new"|"used"|"damaged"|"refurbished",
          "brandIndicators":     string[],
          "suggestedCategories": string[],
          "estimatedPriceUSD":   number|null,
          "rawDescription":      string
        }

        Rules:
        - If a field cannot be determined, use null (arrays → []).
        - Do not wrap the JSON in markdown fences.
        - Be specific; avoid generic terms like "object" or "item".
        - For "productName", give a clear, marketable product name.
        - For "sizes", detect any visible sizes (S/M/L/XL, dimensions, etc.). Use [] if not visible.
        - For "estimatedPriceUSD", estimate a reasonable retail price in USD based on product type, brand, and quality. Use null if impossible to estimate.
        - For "suggestedCategories", provide category paths like "Electronics > Laptops" or "Furniture > Chairs".
        PROMPT;

    /**
     * HTTP status codes that are safe to retry.
     *
     * @var array<int>
     */
    protected const RETRYABLE_HTTP_CODES = [408, 429, 500, 502, 503, 504];

    public function __construct(
        protected AiApiClient $apiClient,
        protected CredentialRepository $credentialRepository,
    ) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Analyze an image source string and return a populated ImageProductContext.
     *
     * @param  string  $imageContent  URL, base64 data URI, or raw base64 string
     * @param  int  $credentialId  Credential record ID to use for this call
     * @param  array{
     *     systemPrompt?:  string,
     *     maxTokens?:     int,
     *     temperature?:   float,
     *     maxAttempts?:   int,
     *     baseDelayMs?:   int,
     *     capDelayMs?:    int,
     *     locale?:        string,
     * }  $options
     *
     * @throws ApiException When all retry attempts are exhausted
     */
    public function analyze(string $imageContent, int $credentialId = 0, ?AiApiClient $apiClient = null, array $options = []): ImageProductContext
    {
        // Use pre-configured client if provided, otherwise build from credential/config
        if ($apiClient) {
            $this->apiClient = $apiClient;
            $provider = $apiClient->getProvider();
            $model = $apiClient->getModel();
        } elseif ($credentialId > 0) {
            $credential = $this->credentialRepository->findOrFail($credentialId);
            $config = CredentialConfig::fromModel($credential);
            $this->apiClient->configure($config);
            $provider = $config->provider;
            $model = $config->model;
        } else {
            $config = $this->buildMagicAiConfig();
            $this->apiClient->configure($config);
            $provider = $config->provider;
            $model = $config->model;
        }

        $systemPrompt = $options['systemPrompt'] ?? self::DEFAULT_SYSTEM_PROMPT;
        $maxTokens = (int) ($options['maxTokens'] ?? 2048);
        $temperature = (float) ($options['temperature'] ?? 0.2);
        $maxAttempts = (int) ($options['maxAttempts'] ?? 1);
        $baseDelayMs = (int) ($options['baseDelayMs'] ?? 300);
        $capDelayMs = (int) ($options['capDelayMs'] ?? 2_000);
        $locale = (string) ($options['locale'] ?? 'en');

        $messages = $this->buildVisionMessages($provider, $imageContent, $systemPrompt, $locale);

        $rawResponse = $this->callWithRetry(
            fn () => $this->apiClient->chat($messages, $maxTokens, $temperature),
            $maxAttempts,
            $baseDelayMs,
            $capDelayMs,
        );

        return $this->buildContext($imageContent, $rawResponse, $model);
    }

    /**
     * Convenience overload: enrich an existing ImageProductContext in-place.
     *
     * The existing context fields are preserved; only the fields that can be
     * derived from vision (detectedProduct, attributes, category, rawAiResponse)
     * are updated.
     *
     * @param  array<string, mixed>  $options  Same as {@see analyze()}
     *
     * @throws ApiException
     */
    public function analyzeContext(
        ImageProductContext $ctx,
        int $credentialId,
        array $options = [],
    ): ImageProductContext {
        $imagePath = $ctx->imagePath;

        if (empty($imagePath)) {
            throw new \InvalidArgumentException(
                'VisionService::analyzeContext requires a non-empty ImageProductContext::$imagePath.',
            );
        }

        $result = $this->analyze($imagePath, $credentialId, $options);

        // Merge: preserve existing attributes / enrichment, overlay vision data
        return new ImageProductContext(
            imagePath: $ctx->imagePath,
            detectedProduct: $result->detectedProduct ?? $ctx->detectedProduct,
            attributes: array_merge($ctx->attributes, $result->attributes),
            category: $result->category ?? $ctx->category,
            enrichment: $ctx->enrichment,
            confidence: $ctx->confidence,
            rawAiResponse: $result->rawAiResponse ?? $ctx->rawAiResponse,
            productId: $ctx->productId,
        );
    }

    // -------------------------------------------------------------------------
    // Message building — provider-specific
    // -------------------------------------------------------------------------

    /**
     * Build the messages array according to the provider's vision spec.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildVisionMessages(
        string $provider,
        string $imageContent,
        string $systemPrompt,
        string $locale,
    ): array {
        $localeNote = $locale !== 'en' ? " Respond in locale: $locale." : '';

        return match ($provider) {
            'openai'    => $this->openaiMessages($imageContent, $systemPrompt, $localeNote),
            'anthropic' => $this->anthropicMessages($imageContent, $systemPrompt, $localeNote),
            default     => $this->genericMessages($imageContent, $systemPrompt, $localeNote),
        };
    }

    /**
     * OpenAI GPT-4 Vision / GPT-4o multi-modal message format.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function openaiMessages(string $imageContent, string $systemPrompt, string $localeNote): array
    {
        $imageBlock = str_starts_with($imageContent, 'data:')
            ? ['type' => 'image_url', 'image_url' => ['url' => $imageContent, 'detail' => 'high']]
            : ['type' => 'image_url', 'image_url' => ['url' => $imageContent, 'detail' => 'high']];

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            [
                'role'    => 'user',
                'content' => [
                    $imageBlock,
                    ['type' => 'text', 'text' => 'Analyze this product image and return the JSON.'.$localeNote],
                ],
            ],
        ];
    }

    /**
     * Anthropic Claude 3+ vision message format.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function anthropicMessages(string $imageContent, string $systemPrompt, string $localeNote): array
    {
        if (str_starts_with($imageContent, 'data:')) {
            // data:image/jpeg;base64,<data>
            preg_match('#^data:([^;]+);base64,(.+)$#s', $imageContent, $m);
            $mediaType = $m[1] ?? 'image/jpeg';
            $b64data = $m[2] ?? '';

            $imageBlock = [
                'type'   => 'image',
                'source' => ['type' => 'base64', 'media_type' => $mediaType, 'data' => $b64data],
            ];
        } else {
            $imageBlock = [
                'type'   => 'image',
                'source' => ['type' => 'url', 'url' => $imageContent],
            ];
        }

        return [
            // system message is extracted by AiApiClient::extractSystemMessage()
            ['role' => 'system', 'content' => $systemPrompt],
            [
                'role'    => 'user',
                'content' => [
                    $imageBlock,
                    ['type' => 'text', 'text' => 'Analyze this product image and return the JSON.'.$localeNote],
                ],
            ],
        ];
    }

    /**
     * Fallback: embed the image reference as plain text for providers that
     * don't support structured vision blocks.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function genericMessages(string $imageContent, string $systemPrompt, string $localeNote): array
    {
        $imageRef = str_starts_with($imageContent, 'data:')
            ? '[base64-encoded image]'
            : $imageContent;

        return [
            ['role' => 'system', 'content' => $systemPrompt],
            [
                'role'    => 'user',
                'content' => "Analyze this product image: $imageRef".$localeNote,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Response parsing
    // -------------------------------------------------------------------------

    /**
     * Parse the raw API response into a populated ImageProductContext.
     *
     * @param  array{content: string, tokensUsed: int, raw: array<mixed>}  $response
     */
    protected function buildContext(string $imageSource, array $response, string $model): ImageProductContext
    {
        $raw = $response['content'] ?? '';
        $detected = $this->parseDetections($raw);

        // Derive top-level DTO fields from detection data
        $detectedProduct = (string) ($detected['productType'] ?? '');

        $category = null;
        if (! empty($detected['suggestedCategories'])) {
            $category = is_array($detected['suggestedCategories'])
                ? ($detected['suggestedCategories'][0] ?? null)
                : $detected['suggestedCategories'];
        }

        // Flatten detection fields to PIM attribute codes
        $attributes = $this->detectionToAttributes($detected);

        return new ImageProductContext(
            imagePath: $imageSource,
            detectedProduct: $detectedProduct ?: null,
            attributes: $attributes,
            category: $category ? (string) $category : null,
            enrichment: [],
            confidence: [],
            rawAiResponse: $raw,
            productId: null,
        );
    }

    /**
     * Map vision detection fields to PIM attribute codes.
     *
     * @param  array<string, mixed>  $detected
     * @return array<string, mixed>
     */
    protected function detectionToAttributes(array $detected): array
    {
        $map = [
            'productType'         => 'product_type',
            'productName'         => 'detected_name',
            'colors'              => 'color',
            'sizes'               => 'size',
            'materials'           => 'material',
            'patterns'            => 'pattern',
            'style'               => 'style',
            'condition'           => 'condition',
            'brandIndicators'     => 'brand',
            'suggestedCategories' => 'categories',
            'rawDescription'      => 'description',
            'estimatedPriceUSD'   => 'estimated_price',
        ];

        $attributes = [];

        foreach ($map as $detectionKey => $pimKey) {
            if (! array_key_exists($detectionKey, $detected)) {
                continue;
            }

            $value = $detected[$detectionKey];

            // Join simple multi-value fields — take first value for single-value PIM attributes
            if (is_array($value) && in_array($detectionKey, ['colors', 'sizes', 'materials', 'patterns', 'brandIndicators'], true)) {
                // For color/size, use only the first detected value (PIM expects single value)
                if (in_array($detectionKey, ['colors', 'sizes'], true)) {
                    $value = array_filter($value);
                    $value = ! empty($value) ? reset($value) : null;
                } else {
                    $value = implode(', ', array_filter($value));
                }
            }

            if ($value !== null && $value !== '' && $value !== []) {
                $attributes[$pimKey] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Decode JSON from the raw AI response, stripping any markdown fences.
     *
     * @return array<string, mixed>
     */
    protected function parseDetections(string $raw): array
    {
        // Strip ```json ... ``` or ``` ... ``` fences
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $clean = preg_replace('/\s*```\s*$/m', '', $clean ?? $raw);
        $decoded = json_decode(trim($clean ?? ''), true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Graceful fallback: store raw text as a description
        return ['rawDescription' => $raw];
    }

    // -------------------------------------------------------------------------
    // Retry logic
    // -------------------------------------------------------------------------

    /**
     * Execute $operation, retrying on retriable failures up to $maxAttempts.
     *
     * Uses truncated exponential backoff with full jitter:
     *   delay = random(0, min($capMs, $baseMs * 2^attempt))
     *
     * @template T
     *
     * @param  callable(): T  $operation
     * @return T
     *
     * @throws ApiException When all attempts fail
     */
    protected function callWithRetry(
        callable $operation,
        int $maxAttempts,
        int $baseDelayMs,
        int $capDelayMs,
    ): mixed {
        $lastException = null;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                return $operation();
            } catch (ApiException $e) {
                $lastException = $e;

                if (! $this->isRetryable($e) || $attempt === $maxAttempts - 1) {
                    throw $e;
                }

                $ceilMs = min($capDelayMs, $baseDelayMs * (2 ** $attempt));
                $delayMs = random_int(0, $ceilMs);

                usleep($delayMs * 1_000);
            }
        }

        throw $lastException ?? new ApiException('VisionService: all retry attempts exhausted.');
    }

    /**
     * Determine whether an ApiException is safe to retry.
     */
    protected function isRetryable(ApiException $e): bool
    {
        $code = $e->getCode();

        // cURL errors (numeric codes < 100) are always retried
        if ($code > 0 && $code < 100) {
            return true;
        }

        return in_array($code, self::RETRYABLE_HTTP_CODES, true);
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

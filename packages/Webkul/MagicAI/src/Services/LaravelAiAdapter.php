<?php

namespace Webkul\MagicAI\Services;

use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Image;
use Webkul\MagicAI\Contracts\LLMModelInterface;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * Adapter that bridges MagicAI's LLMModelInterface with the Laravel AI SDK.
 *
 * Uses laravel/ai's AnonymousAgent for text generation and Laravel\Ai\Image
 * for image generation. Per-request provider config (api_key, api_url, extras)
 * is pushed into the runtime `ai.providers.*` config so the laravel/ai gateway
 * picks them up for the duration of the call.
 */
class LaravelAiAdapter implements LLMModelInterface
{
    protected AiProvider $aiProvider;

    public function __construct(
        protected MagicAIPlatform $platform,
        protected string $model,
        protected string $prompt,
        protected float $temperature = 0.7,
        protected int $maxTokens = 1054,
        protected string $systemPrompt = '',
        protected bool $stream = false,
    ) {
        $this->aiProvider = AiProvider::from($this->platform->provider);
        $this->configureProvider();
    }

    /**
     * Push the platform's credentials and overrides into the laravel/ai
     * runtime config under the provider's config key.
     */
    protected function configureProvider(): void
    {
        $configKey = $this->aiProvider->configKey();

        config([
            "ai.providers.{$configKey}.key" => $this->platform->api_key,
        ]);

        if ($this->platform->api_url) {
            config([
                "ai.providers.{$configKey}.url" => $this->platform->api_url,
            ]);
        }

        if ($this->platform->extras && is_array($this->platform->extras)) {
            foreach ($this->platform->extras as $key => $value) {
                config([
                    "ai.providers.{$configKey}.{$key}" => $value,
                ]);
            }
        }
    }

    /**
     * Generate text content using laravel/ai's AnonymousAgent.
     *
     * Reasoning models (o-series, gpt-5*) need higher token budgets to absorb
     * internal reasoning tokens before any visible output is produced; we
     * surface that via the runtime provider config (max_tokens / temperature)
     * since laravel/ai's prompt() API doesn't expose them as call-site params.
     */
    public function ask(): string
    {
        $isReasoningModel = $this->isReasoningModel($this->model);
        $configKey = $this->aiProvider->configKey();

        // Reasoning models need bigger headroom; everyone else uses the caller's maxTokens.
        $effectiveMaxTokens = $isReasoningModel ? max($this->maxTokens, 16000) : $this->maxTokens;

        config([
            "ai.providers.{$configKey}.max_tokens" => $effectiveMaxTokens,
        ]);

        // Reasoning models reject `temperature` (only the default 1.0 is allowed).
        if (! $isReasoningModel) {
            config([
                "ai.providers.{$configKey}.temperature" => $this->temperature,
            ]);
        }

        $agent = new AnonymousAgent(
            instructions: $this->systemPrompt,
            messages: [],
            tools: [],
        );

        $response = $agent->prompt(
            $this->prompt,
            provider: $this->aiProvider->toLab(),
            model: $this->model,
            timeout: 120,
        );

        return $response->text;
    }

    /**
     * OpenAI's reasoning models (o-series, gpt-5*) reject `temperature` and
     * benefit from larger max_tokens because internal reasoning consumes
     * tokens before any visible output is produced.
     */
    protected function isReasoningModel(string $model): bool
    {
        return (bool) preg_match('/^chat-latest|^o[1-9]|^o[1-9]-|^gpt-5/i', $model);
    }

    /**
     * Generate images using laravel/ai's Image::of() static API.
     *
     * Provider-specific options (n, size, quality, response_format) are
     * pushed into the runtime config so the gateway picks them up.
     *
     * @param  array<string, mixed>  $options
     * @return array<int, array{url: string}>
     */
    public function images(array $options): array
    {
        if (! $this->aiProvider->supportsImages()) {
            throw new \RuntimeException(
                "Provider '{$this->aiProvider->label()}' does not support image generation. Use OpenAI, Gemini, or xAI."
            );
        }

        $configKey = $this->aiProvider->configKey();

        $providerOptions = array_filter([
            'n'       => isset($options['n']) ? (int) $options['n'] : null,
            'quality' => $options['quality'] ?? null,
        ]);

        // DALL-E defaults to returning a hosted URL; force base64 so the
        // frontend can apply the image without an extra fetch and so the image
        // survives OpenAI's URL expiration window. gpt-image-1 always returns
        // base64 and rejects this param, so only set it for dall-e-*.
        if (str_starts_with($this->model, 'dall-e')) {
            $providerOptions['response_format'] = 'b64_json';
        }

        foreach ($providerOptions as $key => $value) {
            config(["ai.providers.{$configKey}.{$key}" => $value]);
        }

        $pending = Image::of($this->prompt)
            ->timeout(120);

        if (! empty($options['size'])) {
            $pending->size($options['size']);
        }

        $response = $pending->generate(
            provider: $this->aiProvider->toLab(),
            model: $this->model,
        );

        $images = [];

        foreach ($response->images as $image) {
            // Providers return either base64 (gpt-image-1) or a hosted URL
            // (DALL-E default). Frontend needs a usable URL — prefer base64
            // data URL, fall back to URL.
            $base64 = $image->base64 ?? null;
            $url = $image->url ?? null;
            $mimeType = $image->mimeType ?? 'image/png';

            if (! empty($base64)) {
                $images[] = ['url' => sprintf('data:%s;base64,%s', $mimeType, $base64)];
            } elseif (! empty($url)) {
                $images[] = ['url' => $url];
            }
        }

        return $images;
    }
}

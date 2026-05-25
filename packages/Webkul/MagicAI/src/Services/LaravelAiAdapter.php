<?php

namespace Webkul\MagicAI\Services;

use Prism\Prism\Enums\Provider as PrismProvider;
use Prism\Prism\Facades\Prism;
use Webkul\MagicAI\Contracts\LLMModelInterface;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * Adapter that bridges MagicAI's LLMModelInterface with the Laravel AI SDK.
 *
 * Uses Prism directly for both text and image generation, giving full
 * control over parameters and avoiding SDK-level defaults that break
 * certain models.
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
     * Configure the Laravel AI SDK / Prism provider dynamically from the platform record.
     */
    protected function configureProvider(): void
    {
        $configKey = $this->aiProvider->configKey();

        config([
            "ai.providers.{$configKey}.key"        => $this->platform->api_key,
            "prism.providers.{$configKey}.api_key" => $this->platform->api_key,
        ]);

        if ($this->platform->api_url) {
            config([
                "ai.providers.{$configKey}.url"     => $this->platform->api_url,
                "prism.providers.{$configKey}.url"  => $this->platform->api_url,
            ]);
        }

        if ($this->platform->extras && is_array($this->platform->extras)) {
            foreach ($this->platform->extras as $key => $value) {
                config([
                    "ai.providers.{$configKey}.{$key}"     => $value,
                    "prism.providers.{$configKey}.{$key}"  => $value,
                ]);
            }
        }
    }

    /**
     * Generate text content using Prism directly.
     *
     * This is faster than going through the Laravel AI SDK's Agent pattern
     * because it skips middleware, events, tool invocation, and conversation handling.
     * It also gives us full control over temperature and maxTokens.
     */
    public function ask(): string
    {
        $prismProvider = $this->toPrismProvider();

        $isReasoningModel = $this->isReasoningModel($this->model);

        // Reasoning models (o-series, gpt-5*) need higher max_tokens to account for internal reasoning.
        $maxTokens = $isReasoningModel ? max($this->maxTokens, 16000) : $this->maxTokens;

        $request = Prism::text()
            ->using($prismProvider, $this->model, [
                'api_key' => $this->platform->api_key,
            ])
            ->withMaxTokens($maxTokens)
            ->withClientOptions(['timeout' => 120]);

        // Reasoning models reject the `temperature` parameter (only the default 1.0 is allowed).
        if (! $isReasoningModel) {
            $request->usingTemperature($this->temperature);
        }

        if ($this->systemPrompt) {
            $request->withSystemPrompt($this->systemPrompt);
        }

        $response = $request
            ->withPrompt($this->prompt)
            ->asText();

        return $response->text;
    }

    /**
     * OpenAI's reasoning models (o-series, gpt-5*) reject `temperature` and
     * benefit from larger max_tokens because internal reasoning consumes
     * tokens before any visible output is produced.
     */
    protected function isReasoningModel(string $model): bool
    {
        return (bool) preg_match('/^o[1-9]|^o[1-9]-|^gpt-5/i', $model);
    }

    /**
     * Generate images using Prism directly.
     *
     * Bypasses Laravel AI SDK's Image::of() because its OpenAiProvider
     * hardcodes provider-specific defaults (quality, moderation) that
     * break models which don't support them (e.g. DALL-E 2/3).
     * Calling Prism directly lets us send only the options the user set.
     *
     * @see https://github.com/laravel/ai/issues/255
     */
    public function images(array $options): array
    {
        if (! $this->aiProvider->supportsImages()) {
            throw new \RuntimeException(
                "Provider '{$this->aiProvider->label()}' does not support image generation. Use OpenAI, Gemini, or xAI."
            );
        }

        $providerOptions = array_filter([
            'n'       => isset($options['n']) ? (int) $options['n'] : null,
            'size'    => $options['size'] ?? null,
            'quality' => $options['quality'] ?? null,
        ]);

        // DALL-E defaults to returning a hosted URL; force base64 so the frontend
        // can apply the image without an extra fetch and so the image survives
        // OpenAI's URL expiration window. gpt-image-1 always returns base64
        // and rejects this param, so only set it for dall-e-*.
        if (str_starts_with($this->model, 'dall-e')) {
            $providerOptions['response_format'] = 'b64_json';
        }

        $response = Prism::image()
            ->using($this->toPrismProvider(), $this->model, [
                'api_key' => $this->platform->api_key,
            ])
            ->withPrompt($this->prompt)
            ->withProviderOptions($providerOptions)
            ->withClientOptions(['timeout' => 120])
            ->generate();

        $images = [];

        foreach ($response->images as $image) {
            // Providers return either base64 (gpt-image-1) or a hosted URL (DALL-E default).
            // Frontend needs a usable URL — prefer base64 data URL, fall back to URL.
            if (! empty($image->base64)) {
                $url = sprintf('data:%s;base64,%s', $image->mimeType ?? 'image/png', $image->base64);
            } elseif (! empty($image->url)) {
                $url = $image->url;
            } else {
                continue;
            }

            $images[] = ['url' => $url];
        }

        return $images;
    }

    /**
     * Map AiProvider to Prism's Provider enum.
     */
    protected function toPrismProvider(): PrismProvider
    {
        return match ($this->aiProvider) {
            AiProvider::OpenAI     => PrismProvider::OpenAI,
            AiProvider::Anthropic  => PrismProvider::Anthropic,
            AiProvider::Gemini     => PrismProvider::Gemini,
            AiProvider::Groq       => PrismProvider::Groq,
            AiProvider::Ollama     => PrismProvider::Ollama,
            AiProvider::XAI        => PrismProvider::XAI,
            AiProvider::Mistral    => PrismProvider::Mistral,
            AiProvider::DeepSeek   => PrismProvider::DeepSeek,
            AiProvider::Azure      => PrismProvider::OpenAI,
            AiProvider::OpenRouter => PrismProvider::OpenRouter,
            // See AiProvider::Custom — Prism's Groq provider posts to
            // /chat/completions, the legacy endpoint every OpenAI-compatible
            // third party (Cerebras, Together, Fireworks, etc.) implements.
            AiProvider::Custom => PrismProvider::Groq,
        };
    }
}

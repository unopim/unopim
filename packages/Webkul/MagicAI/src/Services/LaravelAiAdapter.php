<?php

namespace Webkul\MagicAI\Services;

use Laravel\Ai\Image;
use Webkul\MagicAI\Agents\MagicContentAgent;
use Webkul\MagicAI\Agents\TranslationAgent;
use Webkul\MagicAI\Contracts\LLMModelInterface;
use Webkul\MagicAI\Contracts\SupportsStructuredTranslation;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * Adapter that bridges MagicAI's LLMModelInterface with the Laravel AI SDK.
 *
 * Provider credentials are applied through ScopedProviderConfig so they never
 * leak across Octane requests; generation options travel on the agent itself.
 */
class LaravelAiAdapter implements LLMModelInterface, SupportsStructuredTranslation
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
    }

    /**
     * The provider config overrides for this platform record.
     *
     * @return array<string, mixed>
     */
    protected function providerOverrides(): array
    {
        $overrides = [
            'key' => $this->platform->api_key,
        ];

        if ($this->aiProvider === AiProvider::Custom) {
            // Never fall back to the global openai-compatible env URL — that
            // would send this platform's API key to an unrelated host.
            if (! $this->platform->api_url) {
                throw new \RuntimeException(
                    trans('admin::app.configuration.platform.message.custom-api-url-required')
                );
            }

            $overrides['url'] = $this->platform->api_url;
        } elseif ($this->platform->api_url) {
            $overrides['url'] = $this->platform->api_url;
        }

        if ($this->platform->extras && is_array($this->platform->extras)) {
            $overrides = array_merge($overrides, $this->platform->extras);
        }

        return $overrides;
    }

    /**
     * Generate text content using the MagicContentAgent.
     */
    public function ask(): string
    {
        $response = ScopedProviderConfig::run(
            $this->aiProvider->configKey(),
            $this->providerOverrides(),
            fn () => $this->contentAgent()->prompt(
                $this->prompt,
                provider: $this->aiProvider->toLab(),
                model: $this->model,
                timeout: 120,
            ),
        );

        return $response->text;
    }

    /**
     * Translate the configured prompt via structured output.
     */
    public function translate(): string
    {
        $agent = new TranslationAgent(systemPrompt: $this->systemPrompt);

        $response = ScopedProviderConfig::run(
            $this->aiProvider->configKey(),
            $this->providerOverrides(),
            fn () => $agent->prompt(
                $this->prompt,
                provider: $this->aiProvider->toLab(),
                model: $this->model,
                timeout: 120,
            ),
        );

        // Providers without real JSON-schema support (DeepSeek, Ollama, some
        // OpenAI-compatible endpoints) may ignore the schema; fall back to
        // the raw reply, which the prompt constrains to translated HTML.
        $translated = $response['translated_html'] ?? null;

        if (! is_string($translated) || trim($translated) === '') {
            $translated = $response->text;
        }

        return trim((string) $translated);
    }

    /**
     * Build the content agent, giving reasoning models the token headroom
     * they need and omitting the temperature they reject.
     */
    protected function contentAgent(): MagicContentAgent
    {
        $isReasoningModel = $this->isReasoningModel($this->model);

        return new MagicContentAgent(
            systemPrompt: $this->systemPrompt,
            temperature: $isReasoningModel ? null : $this->temperature,
            maxTokens: $isReasoningModel ? max($this->maxTokens, 16000) : $this->maxTokens,
        );
    }

    /**
     * Determine if the model is an OpenAI reasoning model (o-series, gpt-5*).
     */
    protected function isReasoningModel(string $model): bool
    {
        $model = strtolower($model);

        // gpt-5-chat* models accept temperature and are not reasoning models.
        if (str_starts_with($model, 'gpt-5-chat')) {
            return false;
        }

        return (bool) preg_match('/^chat-latest|^o[1-9]\b|^o[1-9]-|^gpt-5/', $model);
    }

    /**
     * Generate images using laravel/ai's Image API.
     *
     * @param  array<string, mixed>  $options
     * @return array<int, array{url: string}>
     */
    public function images(array $options): array
    {
        if (! $this->aiProvider->supportsImages()) {
            throw new \RuntimeException(
                trans('admin::app.configuration.platform.message.images-unsupported', ['provider' => $this->aiProvider->label()])
            );
        }

        $pending = Image::of($this->prompt)
            ->timeout(120);

        if (! empty($options['size'])) {
            $pending->size($options['size']);
        }

        if (! empty($options['quality'])) {
            $pending->quality($options['quality']);
        }

        $response = ScopedProviderConfig::run(
            $this->aiProvider->configKey(),
            $this->providerOverrides(),
            fn () => $pending->generate(
                provider: $this->aiProvider->toLab(),
                model: $this->model,
            ),
        );

        $images = [];

        foreach ($response->images as $image) {
            // Prefer a base64 data URL so the image survives the provider's
            // hosted-URL expiration window; fall back to the URL.
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

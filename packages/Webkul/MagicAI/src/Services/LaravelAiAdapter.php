<?php

namespace Webkul\MagicAI\Services;

use Laravel\Ai\Image;
use Prism\Prism\Enums\Provider as PrismProvider;
use Prism\Prism\Facades\Prism;
use Webkul\MagicAI\Contracts\LLMModelInterface;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * Adapter that bridges MagicAI's LLMModelInterface with the Laravel AI SDK.
 *
 * Uses Prism directly for text generation (fast, full control over temperature/maxTokens),
 * and Laravel AI SDK's Image::of() for image generation.
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

        // Reasoning models (o-series) need higher max_tokens to account for internal reasoning
        $maxTokens = $this->maxTokens;
        if (preg_match('/^o[1-9]|^o[1-9]-/', $this->model)) {
            $maxTokens = max($maxTokens, 16000);
        }

        $request = Prism::text()
            ->using($prismProvider, $this->model, [
                'api_key' => $this->platform->api_key,
            ])
            ->usingTemperature($this->temperature)
            ->withMaxTokens($maxTokens)
            ->withClientOptions(['timeout' => 120]);

        if ($this->systemPrompt) {
            $request->withSystemPrompt($this->systemPrompt);
        }

        $response = $request
            ->withPrompt($this->prompt)
            ->asText();

        return $response->text;
    }

    /**
     * Generate images using the Laravel AI SDK's Image::of() API.
     */
    public function images(array $options): array
    {
        if (! $this->aiProvider->supportsImages()) {
            throw new \RuntimeException(
                "Provider '{$this->aiProvider->label()}' does not support image generation. Use OpenAI, Gemini, or xAI."
            );
        }

        $pending = Image::of($this->prompt);

        if (isset($options['size'])) {
            $sizeMap = [
                '1024x1024' => '1:1',
                '1024x1792' => '2:3',
                '1792x1024' => '3:2',
            ];

            $pending->size($sizeMap[$options['size']] ?? '1:1');
        }

        if (isset($options['quality'])) {
            $qualityMap = [
                'standard' => 'medium',
                'hd'       => 'high',
            ];

            $pending->quality($qualityMap[$options['quality']] ?? 'medium');
        }

        $response = $pending->generate(
            provider: $this->aiProvider->toLab(),
            model: $this->model,
        );

        $images = [];

        foreach ($response->images as $image) {
            $mime = $image->mime ?? 'image/png';
            $images[] = [
                'url' => sprintf('data:%s;base64,%s', $mime, $image->image),
            ];
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
        };
    }
}

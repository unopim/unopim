<?php

namespace Webkul\MagicAI\Services;

use Laravel\Ai\Image;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\ImageResponse;
use Webkul\MagicAI\Agents\MagicContentAgent;
use Webkul\MagicAI\Agents\TranslationAgent;
use Webkul\MagicAI\Contracts\LLMModelInterface;
use Webkul\MagicAI\Contracts\ReportsTruncation;
use Webkul\MagicAI\Contracts\SupportsStructuredTranslation;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Responses\GeneratedContent;

/**
 * Adapter that bridges MagicAI's LLMModelInterface with the Laravel AI SDK.
 *
 * Provider credentials are applied through ScopedProviderConfig so they never
 * leak across Octane requests; generation options travel on the agent itself.
 */
class LaravelAiAdapter implements LLMModelInterface, ReportsTruncation, SupportsStructuredTranslation
{
    protected AiProvider $aiProvider;

    public function __construct(
        protected MagicAIPlatform $platform,
        protected string $model,
        protected string $prompt,
        protected float $temperature = 0.7,
        protected int $maxTokens = MagicAI::DEFAULT_MAX_TOKENS,
        protected string $systemPrompt = '',
        protected bool $stream = false,
    ) {
        $this->aiProvider = AiProvider::from($this->platform->provider);
    }

    /**
     * The model identifier sent to the provider. Azure addresses a model by its
     * deployment name, so a configured deployment wins over the catalogue entry.
     */
    protected function modelName(): string
    {
        if ($this->aiProvider !== AiProvider::Azure) {
            return $this->model;
        }

        $deployment = trim((string) (ProviderOverrides::decode($this->platform->extras)['deployment'] ?? ''));

        return $deployment !== '' ? $deployment : $this->model;
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

        if ($this->aiProvider === AiProvider::Concentrate) {
            $overrides['url'] = $this->platform->api_url ?: $this->aiProvider->defaultUrl();
        } elseif ($this->aiProvider === AiProvider::Custom) {
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

        return ProviderOverrides::build($overrides, $this->platform->extras);
    }

    /**
     * Generate text content using the MagicContentAgent.
     */
    public function ask(): string
    {
        return $this->askResult()->text;
    }

    /**
     * Generate text content, reporting whether the model was cut off by the
     * token ceiling.
     */
    public function askResult(): GeneratedContent
    {
        $response = ScopedProviderConfig::run(
            $this->aiProvider->configKey(),
            $this->providerOverrides(),
            fn (): AgentResponse => $this->contentAgent()->prompt(
                $this->prompt,
                provider: $this->aiProvider->toLab(),
                model: $this->modelName(),
                timeout: 120,
            ),
        );

        return new GeneratedContent(
            text: self::dropDanglingMarkup($response->text),
            truncated: self::hitTokenCeiling($response),
        );
    }

    /**
     * Whether the provider stopped on the token ceiling rather than finishing.
     */
    public static function hitTokenCeiling(AgentResponse $response): bool
    {
        return $response->steps->last()?->finishReason === FinishReason::Length;
    }

    /**
     * A generation cut mid-tag leaves an unterminated `<...` that the editor
     * would render as stray text, so the fragment is dropped.
     */
    public static function dropDanglingMarkup(string $text): string
    {
        return rtrim((string) preg_replace('/<[^<>]*$/', '', $text));
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
            fn (): AgentResponse => $agent->prompt(
                $this->prompt,
                provider: $this->aiProvider->toLab(),
                model: $this->modelName(),
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
            temperature: $isReasoningModel || $this->rejectsSamplingParameters($this->model) ? null : $this->temperature,
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
     * Determine if the model is a Claude model that rejects `temperature`.
     *
     * Claude 3.x, Haiku 4.x and Opus/Sonnet up to 4.6 accept sampling
     * parameters; newer Claude models return a 400, so any other Claude
     * model omits them rather than failing on the next release.
     */
    protected function rejectsSamplingParameters(string $model): bool
    {
        $model = strtolower($model);

        if (! preg_match('/(^|[.\/:])claude-/', $model)) {
            return false;
        }

        return ! preg_match('/claude-(3|haiku-4|(opus|sonnet)-4(-[0-6])?(-\d{8}|@|$))/', $model);
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
            fn (): ImageResponse => $pending->generate(
                provider: $this->aiProvider->toLab(),
                model: $this->modelName(),
            ),
        );

        $images = [];

        foreach ($response->images as $image) {
            if ($image->image === '') {
                continue;
            }

            $images[] = ['url' => sprintf('data:%s;base64,%s', $image->mime(), $image->image)];
        }

        return $images;
    }
}

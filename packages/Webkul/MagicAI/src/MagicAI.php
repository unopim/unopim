<?php

namespace Webkul\MagicAI;

use Webkul\MagicAI\Contracts\LLMModelInterface;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\LaravelAiAdapter;

class MagicAI
{
    /**
     * @deprecated Use AiProvider enum instead
     */
    const MAGIC_OPEN_AI = 'openai';

    /**
     * @deprecated Use AiProvider enum instead
     */
    const MAGIC_GROQ_AI = 'groq';

    /**
     * @deprecated Use AiProvider enum instead
     */
    const MAGIC_OLLAMA_AI = 'ollama';

    /**
     * @deprecated Use AiProvider enum instead
     */
    const MAGIC_GEMINI_AI = 'gemini';

    const SUFFIX_HTML_PROMPT = 'Generate a response using HTML formatting only. Do not include Markdown or any non-HTML syntax.';

    const SUFFIX_TEXT_PROMPT = 'Generate a response in plain text only, avoiding Markdown or any other formatting.';

    /**
     * AI platform record from database.
     */
    protected ?MagicAIPlatform $platformRecord = null;

    /**
     * LLM model.
     */
    protected ?string $model = null;

    /**
     * Stream Response.
     */
    protected bool $stream = false;

    protected float $temperature = 0.7;

    protected int $maxTokens = 1054;

    /**
     * LLM prompt text.
     */
    protected string $prompt = '';

    /**
     * LLM system prompt text.
     */
    protected string $systemPrompt = '';

    /**
     * Set a platform by its database ID.
     */
    public function setPlatformId(int $id): self
    {
        $this->platformRecord = app(MagicAIPlatformRepository::class)->findOrFail($id);

        return $this;
    }

    /**
     * Set a platform directly.
     */
    public function usePlatform(MagicAIPlatform $platform): self
    {
        $this->platformRecord = $platform;

        return $this;
    }

    /**
     * Use the default active platform.
     */
    public function useDefault(): self
    {
        $this->platformRecord = app(MagicAIPlatformRepository::class)->getDefault();

        return $this;
    }

    /**
     * Set LLM platform.
     *
     * @deprecated Use setPlatformId() or useDefault() instead
     */
    public function setPlatForm(string $platform): self
    {
        $repo = app(MagicAIPlatformRepository::class);

        $this->platformRecord = $repo->findOneWhere([
            'provider' => $platform,
            'status'   => true,
        ]);

        return $this;
    }

    /**
     * Set LLM model.
     */
    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set stream response.
     */
    public function setStream(bool $stream): self
    {
        $this->stream = $stream;

        return $this;
    }

    /**
     * Set temperature.
     */
    public function setTemperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * Set the max tokens.
     */
    public function setMaxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    /**
     * Set LLM prompt text.
     */
    public function setPrompt(string $prompt, string $fieldType = 'tinymce'): self
    {
        $this->prompt = $fieldType == 'tinymce' ? $prompt.' '.self::SUFFIX_HTML_PROMPT : $prompt.' '.self::SUFFIX_TEXT_PROMPT;

        return $this;
    }

    /**
     * Set LLM system prompt.
     */
    public function setSystemPrompt(string $systemPrompt): self
    {
        $this->systemPrompt = $systemPrompt;

        return $this;
    }

    /**
     * Generate text content.
     */
    public function ask(): string
    {
        return $this->getModelInstance()->ask();
    }

    /**
     * Generate images.
     */
    public function images(array $options): array
    {
        return $this->getModelInstance()->images($options);
    }

    /**
     * Get LLM model instance.
     */
    public function getModelInstance(): LLMModelInterface
    {
        $platform = $this->platformRecord
            ?? app(MagicAIPlatformRepository::class)->getDefault();

        if (! $platform) {
            throw new \RuntimeException(
                'No AI platform configured. Please add a platform in Configuration > Magic AI > Platforms.'
            );
        }

        $model = $this->model ?? $platform->model_list[0] ?? throw new \RuntimeException(
            'No model configured for the selected platform.'
        );

        return new LaravelAiAdapter(
            platform: $platform,
            model: $model,
            prompt: $this->prompt,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            systemPrompt: $this->systemPrompt,
            stream: $this->stream,
        );
    }

    /**
     * Gets the list of models for the default platform.
     */
    public function getModelList(): array
    {
        $platform = $this->platformRecord
            ?? app(MagicAIPlatformRepository::class)->getDefault();

        if (! $platform) {
            return [];
        }

        return array_map(fn ($model) => [
            'id'    => $model,
            'label' => $model,
        ], $platform->model_list);
    }
}

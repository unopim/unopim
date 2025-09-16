<?php

namespace Webkul\MagicAI;

use Webkul\MagicAI\Contracts\LLMModelInterface;
use Webkul\MagicAI\Services\AIModel;
use Webkul\MagicAI\Services\Claude;
use Webkul\MagicAI\Services\Gemini;
use Webkul\MagicAI\Services\GptOss;
use Webkul\MagicAI\Services\Groq;
use Webkul\MagicAI\Services\Ollama;
use Webkul\MagicAI\Services\OpenAI;

class MagicAI
{
    const MAGIC_GPT_OSS = 'gpt_oss';

    const MAGIC_OPEN_AI = 'openai';

    const MAGIC_GROQ_AI = 'groq';

    const MAGIC_OLLAMA_AI = 'ollama';

    const MAGIC_CLAUDE_AI = 'claude';

    const MAGIC_GEMINI_AI = 'gemini';

    const SUFFIX_HTML_PROMPT = 'Generate a response using HTML formatting only. Do not include Markdown or any non-HTML syntax.';

    const SUFFIX_TEXT_PROMPT = 'Generate a response in plain text only, avoiding Markdown or any other formatting.';

    /**
     * AI platform.
     */
    protected string $platform;

    /**
     * LLM model.
     */
    protected string $model;

    /**
     * LLM agent.
     */
    protected string $agent;

    /**
     * Stream Response.
     */
    protected bool $stream = false;

    /**
     * Raw Response.
     */
    protected bool $raw = true;

    /**
     * Raw Response.
     */
    protected float $temperature = 0.7;

    /**
     * Max tokens.
     */
    protected int $maxTokens = 1054;

    /**
     * LLM prompt text.
     */
    protected string $prompt;

    /**
     * LLM system prompt text.
     */
    protected string $systemPrompt = '';

    /**
     * Set LLM model
     */
    public function setPlatForm(string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * Set LLM model
     */
    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set LLM agent
     */
    public function setAgent(string $agent): self
    {
        $this->agent = $agent;

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
     * Set response raw.
     */
    public function setRaw(bool $raw): self
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * Set LLM prompt text.
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
     * Set LLM prompt text.
     */
    public function ask(): string
    {
        return $this->getModelInstance()->ask();
    }

    /**
     * Generate Images
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
        if ($this->platform === self::MAGIC_GPT_OSS) {
            return new GptOss(
                $this->model,
                $this->prompt,
                $this->temperature,
                $this->stream,
                $this->maxTokens,
                $this->systemPrompt
            );
        }

        if ($this->platform === self::MAGIC_OPEN_AI) {
            return new OpenAI(
                $this->model,
                $this->prompt,
                $this->temperature,
                $this->stream,
                $this->maxTokens,
                $this->systemPrompt
            );
        }

        if ($this->platform === self::MAGIC_GROQ_AI) {
            return new Groq(
                $this->model,
                $this->prompt,
                $this->temperature,
                $this->stream,
                $this->raw,
                $this->maxTokens,
                $this->systemPrompt
            );
        }

        if ($this->platform === self::MAGIC_CLAUDE_AI) {
            return new Claude(
                $this->model,
                $this->prompt,
                $this->temperature,
                $this->stream,
                $this->raw,
                $this->maxTokens,
                $this->systemPrompt
            );
        }

        if ($this->platform === self::MAGIC_GEMINI_AI) {
            return new Gemini(
                $this->model,
                $this->prompt,
                $this->temperature,
                $this->stream,
                $this->raw,
                $this->maxTokens,
                $this->systemPrompt
            );
        }

        return new Ollama(
            $this->model,
            $this->prompt,
            $this->temperature,
            $this->stream,
            $this->raw,
            $this->maxTokens,
            $this->systemPrompt
        );
    }

    /**
     * Gets the list of models from the API.
     */
    public function getModelList(): array
    {
        return AIModel::getModels();
    }
}

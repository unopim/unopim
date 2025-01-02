<?php

namespace Webkul\MagicAI;

use Webkul\MagicAI\Services\AIModel;
use Webkul\MagicAI\Services\Groq;
use Webkul\MagicAI\Services\Ollama;
use Webkul\MagicAI\Services\OpenAI;

class MagicAI
{
    const MAGIC_OPEN_AI = 'openai';

    const MAGIC_GROQ_AI = 'groq';

    const MAGIC_OLLAMA_AI = 'ollama';

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
     * LLM prompt text.
     */
    protected string $prompt;

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
     * Set LLM prompt text.
     */
    public function setPrompt(string $prompt): self
    {
        $this->prompt = $prompt.' '.self::SUFFIX_HTML_PROMPT;

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
    public function getModelInstance(): OpenAI|Groq|Ollama
    {
        if ($this->platform === self::MAGIC_OPEN_AI) {
            return new OpenAI(
                $this->model,
                $this->prompt,
                $this->temperature,
                $this->stream,
            );
        }

        if ($this->platform === self::MAGIC_GROQ_AI) {
            return new Groq(
                $this->model,
                $this->prompt,
                $this->temperature,
                $this->stream,
                $this->raw,
            );
        }

        return new Ollama(
            $this->model,
            $this->prompt,
            $this->temperature,
            $this->stream,
            $this->raw,
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

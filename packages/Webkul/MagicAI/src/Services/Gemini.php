<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;
use Webkul\MagicAI\Contracts\LLMModelInterface;

class Gemini implements LLMModelInterface
{
    /**
     * New service instance.
     */
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected float $temperature,
        protected bool $stream,
        protected bool $raw,
        protected int $maxTokens,
        protected string $systemPrompt,
    ) {}

    /**
     * Set LLM prompt text.
     */
    public function ask(): string
    {
        $httpClient = new Client;
        $apiKey = core()->getConfigData('general.magic_ai.settings.api_key');
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

        $response = $httpClient->post($endpoint, [
            'headers' => [
                'Accept'         => 'application/json',
                'Content-Type'   => 'application/json',
                'x-goog-api-key' => $apiKey,
            ],
            'json' => [
                'contents' => [
                    ['parts' => [['text' => $this->prompt]]],
                ],
                'generationConfig' => [
                    'temperature'     => $this->temperature,
                    'maxOutputTokens' => $this->maxTokens,
                ],
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response';
    }

    /**
     * Generate image.
     */
    public function images(array $options): array
    {
        throw new \RuntimeException('Gemini does not support image generation.');
    }
}

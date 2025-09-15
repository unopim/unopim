<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;
use Webkul\MagicAI\Contracts\LLMModelInterface;

class Groq implements LLMModelInterface
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
        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

        $result = $httpClient->request('POST', $endpoint, [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.core()->getConfigData('general.magic_ai.settings.api_key'),
            ],
            'json'    => [
                'model'       => $this->model,
                'messages'    => [
                    [
                        'role'    => 'system',
                        'content' => $this->systemPrompt,
                    ],
                    [
                        'role'    => 'user',
                        'content' => $this->prompt,
                    ],
                ],
            ],
        ]);

        $result = json_decode($result->getBody()->getContents(), true);

        return $result['choices'][0]['message']['content'];
    }

    /**
     * Generate image.
     */
    public function images(array $options): array
    {
        throw new \RuntimeException('Groq does not support image generation.');
    }
}

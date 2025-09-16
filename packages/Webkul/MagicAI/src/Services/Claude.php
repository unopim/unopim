<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;
use Webkul\MagicAI\Contracts\LLMModelInterface;

class Claude implements LLMModelInterface
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
        $apiKey = core()->getConfigData('general.magic_ai.settings.api_key');

        $httpClient = new Client;
        $apiKey = core()->getConfigData('general.magic_ai.settings.api_key');
        $endpoint = 'https://api.anthropic.com/v1/messages';

        $response = $httpClient->request('POST', $endpoint, [
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
            ],
            'json' => [
                'model'       => $this->model,
                'max_tokens'  => $this->maxTokens,
                'temperature' => $this->temperature,
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

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['content'][0]['text'] ?? 'No response';
    }

    /**
     * Generate image.
     */
    public function images(array $options): array
    {
        throw new \RuntimeException('Claude does not support image generation.');
    }
}

<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;
use OpenAI\ValueObjects\Transporter\BaseUri;

class Claude
{
    /**
     * New service instance.
     */
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected string $systemPrompt,
        protected float $temperature,
        protected bool $stream,
        protected bool $raw,
        protected int $maxTokens,
    ) {}

    /**
     * Set LLM prompt text.
     */
    public function ask(): string
    {
        $apiKey = core()->getConfigData('general.magic_ai.settings.api_key');

        $httpClient = new Client;

        $apiKey = core()->getConfigData('general.magic_ai.settings.api_key');
        $baseUri = BaseUri::from('api.anthropic.com')->toString();
        $endpoint = $baseUri.'v1/messages';

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
}

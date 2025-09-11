<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;
use OpenAI\ValueObjects\Transporter\BaseUri;

class GptOss
{
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected float $temperature,
        protected bool $stream,
        protected int $maxTokens,
        protected string $systemPrompt,
    ) {}

    public function ask(): string
    {
        $httpClient = new Client;

        $apiKey = core()->getConfigData('general.magic_ai.settings.api_key');
        $baseUri = BaseUri::from('openrouter.ai')->toString();
        $endpoint = $baseUri.`api/v1/chat/completions`;

        $response = $httpClient->post($endpoint, [
            'json' => [
                'model'       => $this->model,
                'temperature' => $this->temperature,
                'max_tokens'  => $this->maxTokens,
                'stream'      => $this->stream,
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
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer'.$apiKey,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['choices'][0]['message']['content'] ?? 'No response';
    }
}

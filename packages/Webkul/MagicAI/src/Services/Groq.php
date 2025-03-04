<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;
use OpenAI\ValueObjects\Transporter\BaseUri;

class Groq
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
    ) {}

    /**
     * Set LLM prompt text.
     */
    public function ask(): string
    {
        $httpClient = new Client;

        $baseUri = BaseUri::from('api.groq.com')->toString();
        $endpoint = $baseUri.'openai/v1/chat/completions';

        $result = $httpClient->request('POST', $endpoint, [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.core()->getConfigData('general.magic_ai.settings.api_key'),
            ],
            'json'    => [
                'model'       => $this->model,
                'messages'    => [
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
}

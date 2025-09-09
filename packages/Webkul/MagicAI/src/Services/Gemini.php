<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;
use OpenAI\ValueObjects\Transporter\BaseUri;

class Gemini
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
    ) {}

    /**
     * Set LLM prompt text.
     */
    public function ask(): string
    {

        $apiKey = core()->getConfigData('general.magic_ai.settings.api_key');
        $baseUri = BaseUri::from('generativelanguage.googleapis.com')->toString();
        $endpoint = $baseUri.`v1beta/models/{$this->model}:generateContent`;

        $httpClient = new Client;

        $response = $httpClient->post($endpoint, [
            'headers' => [
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
}

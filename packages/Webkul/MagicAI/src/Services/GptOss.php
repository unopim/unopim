<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;

class GptOss
{
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected float $temperature,
        protected bool $stream,
        protected bool $raw,
        protected int $maxTokens,
        protected string $apiUrl = 'localhost', // here need local url for, where this model is running locally
    ) {}

    public function ask(): string
    {
        $httpClient = new Client;

        $response = $httpClient->post($this->apiUrl, [
            'json' => [
                'model'       => $this->model,
                'prompt'      => $this->prompt,
                'temperature' => $this->temperature,
                'max_tokens'  => $this->maxTokens,
                'stream'      => $this->stream,
                'raw'         => $this->raw,
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['response'] ?? 'No response';

    }
}

<?php

namespace Webkul\MagicAI\Services;

use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI as BaseOpenAI;

class OpenAI
{
    /**
     * New service instance.
     */
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected float $temperature,
        protected bool $stream = false
    ) {
        $this->setConfig();
    }

    /**
     * Sets OpenAI credentials.
     */
    public function setConfig(): void
    {
        config([
            'openai.api_key'      => core()->getConfigData('general.magic_ai.settings.api_key'),
            'openai.organization' => core()->getConfigData('general.magic_ai.settings.organization'),
        ]);
    }

    /**
     * Set LLM prompt text.
     */
    public function ask(): string
    {
        $result = BaseOpenAI::chat()->create([
            'model'       => $this->model,
            'temperature' => $this->temperature,
            'messages'    => [
                [
                    'role'    => 'user',
                    'content' => $this->prompt,
                ],
            ],
        ]);

        return $result->choices[0]->message->content;
    }

    /**
     * Generate image.
     */
    public function images(array $options): array
    {
        $extraParameters = [];

        if (isset($options['quality']) && $this->model !== 'dall-e-2') {
            $extraParameters['quality'] = $options['quality'];
        }

        $result = BaseOpenAI::images()->create(array_merge([
            'model'           => $this->model,
            'prompt'          => $this->prompt,
            'n'               => intval($options['n'] ?? 1),
            'size'            => $options['size'],
            'response_format' => 'b64_json',
        ], $extraParameters));

        $images = [];

        foreach ($result->data as $image) {
            $images[]['url'] = 'data:image/png;base64,'.$image->b64_json;
        }

        return $images;
    }

    public function generateImage(?string $imageBase64, string $mimeType, string $prompt): array
    {
        $apiKey = env('GEMINI_API_KEY');

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data'      => $imageBase64,
                        ],
                    ],
                ],
            ]],
            'generationConfig' => [
                'responseModalities' => ['TEXT', 'IMAGE'],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}-flash-preview-image-generation:generateContent?key={$apiKey}",
            $payload
        );

        if (! $response->successful()) {
            throw new \Exception($response->json('error.message') ?? 'Failed to generate image from Gemini.');
        }

        $parts = data_get($response->json(), 'candidates.0.content.parts', []);
        $images = [];

        foreach ($parts as $part) {
            if (isset($part['inlineData']['data']) && isset($part['inlineData']['mimeType'])) {
                $base64 = $part['inlineData']['data'];
                $mime = $part['inlineData']['mimeType'];
                $images[]['url'] = "data:{$mime};base64,{$base64}";
            }
        }

        if (empty($images)) {
            throw new \Exception('No base64 image returned from Gemini.');
        }

        return $images;
    }

    public function editImage(array $options, $imageFile, string $prompt): array
    {
        $imageContent = file_get_contents($imageFile->getRealPath());
        $originalName = $imageFile->getClientOriginalName();

        $http = Http::withHeaders([
            'Authorization'       => 'Bearer '.config('openai.api_key'),
            'OpenAI-Organization' => config('openai.organization'),
        ])
            ->timeout(60)
            ->attach('image', $imageContent, $originalName)
            ->attach('prompt', $prompt)
            ->attach('n', $options['n'] ?? 1)
            ->attach('size', $options['size'] ?? '1024x1024')
            ->attach('model', $this->model);

        if ($this->model === 'dall-e-2') {
            $http->attach('response_format', 'b64_json');
        }

        $response = $http->post('https://api.openai.com/v1/images/edits');

        if (! $response->successful()) {
            throw new \Exception($response->json('error.message') ?? 'Image edit failed.');
        }

        $responseData = $response->json();

        $images = [];

        foreach ($responseData['data'] as $image) {
            $images[]['url'] = 'data:image/png;base64,'.$image['b64_json'];
        }

        return $images;
    }
}

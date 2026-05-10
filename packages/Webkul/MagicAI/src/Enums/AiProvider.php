<?php

namespace Webkul\MagicAI\Enums;

use GuzzleHttp\Client;
use Laravel\Ai\Enums\Lab;
use Prism\Prism\Enums\Provider as PrismProvider;

enum AiProvider: string
{
    case OpenAI = 'openai';
    case Anthropic = 'anthropic';
    case Gemini = 'gemini';
    case Groq = 'groq';
    case Ollama = 'ollama';
    case XAI = 'xai';
    case Mistral = 'mistral';
    case DeepSeek = 'deepseek';
    case Azure = 'azure';
    case OpenRouter = 'openrouter';
    case Custom = 'custom';

    public function toLab(): Lab
    {
        return match ($this) {
            self::OpenAI     => Lab::OpenAI,
            self::Anthropic  => Lab::Anthropic,
            self::Gemini     => Lab::Gemini,
            self::Groq       => Lab::Groq,
            self::Ollama     => Lab::Ollama,
            self::XAI        => Lab::xAI,
            self::Mistral    => Lab::Mistral,
            self::DeepSeek   => Lab::DeepSeek,
            self::Azure      => Lab::Azure,
            self::OpenRouter => Lab::OpenRouter,
            self::Custom     => Lab::OpenAI,
        };
    }

    public function toPrismProvider(): PrismProvider
    {
        return match ($this) {
            self::OpenAI     => PrismProvider::OpenAI,
            self::Anthropic  => PrismProvider::Anthropic,
            self::Gemini     => PrismProvider::Gemini,
            self::Groq       => PrismProvider::Groq,
            self::Ollama     => PrismProvider::Ollama,
            self::XAI        => PrismProvider::XAI,
            self::Mistral    => PrismProvider::Mistral,
            self::DeepSeek   => PrismProvider::DeepSeek,
            self::Azure      => PrismProvider::OpenAI,
            self::OpenRouter => PrismProvider::OpenRouter,
            // Prism's OpenAI provider posts to /responses (new Responses API,
            // OpenAI-only). Most "OpenAI-compatible" third parties (Cerebras,
            // Together, Fireworks, Perplexity, DeepInfra) only implement the
            // legacy /chat/completions endpoint — which is exactly what the
            // Groq Prism provider speaks. Routing Custom through Groq lets
            // any chat-completions-compatible API work out of the box.
            self::Custom => PrismProvider::Groq,
        };
    }

    public function supportsImages(): bool
    {
        return in_array($this, [
            self::OpenAI,
            self::Gemini,
            self::XAI,
        ]);
    }

    public function defaultUrl(): string
    {
        return match ($this) {
            self::OpenAI     => 'https://api.openai.com/v1',
            self::Anthropic  => 'https://api.anthropic.com/v1',
            self::Gemini     => 'https://generativelanguage.googleapis.com/v1beta',
            self::Groq       => 'https://api.groq.com/openai/v1',
            self::Ollama     => 'http://localhost:11434',
            self::XAI        => 'https://api.x.ai/v1',
            self::Mistral    => 'https://api.mistral.ai/v1',
            self::DeepSeek   => 'https://api.deepseek.com',
            self::Azure      => '',
            self::OpenRouter => 'https://openrouter.ai/api/v1',
            self::Custom     => '',
        };
    }

    public function configKey(): string
    {
        return match ($this) {
            self::OpenRouter => 'openrouter',
            // Custom routes through PrismProvider::Groq (chat-completions),
            // so its api_url override must land in the groq config namespace.
            self::Custom => 'groq',
            default      => $this->value,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OpenAI     => 'OpenAI',
            self::Anthropic  => 'Anthropic',
            self::Gemini     => 'Google Gemini',
            self::Groq       => 'Groq',
            self::Ollama     => 'Ollama',
            self::XAI        => 'xAI (Grok)',
            self::Mistral    => 'Mistral',
            self::DeepSeek   => 'DeepSeek',
            self::Azure      => 'Azure OpenAI',
            self::OpenRouter => 'OpenRouter',
            self::Custom     => 'Custom (OpenAI-compatible)',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $provider) => [
            'title' => $provider->label(),
            'value' => $provider->value,
        ], self::cases());
    }

    /**
     * Fetch available models from the provider API.
     */
    public function fetchModels(?string $apiKey, ?string $apiUrl = null): array
    {
        $client = new Client(['timeout' => 15]);

        try {
            return match ($this) {
                self::OpenAI     => $this->fetchOpenAiModels($client, $apiKey),
                self::Anthropic  => $this->fetchAnthropicModels($client, $apiKey),
                self::Gemini     => $this->fetchGeminiModels($client, $apiKey),
                self::Groq       => $this->fetchOpenAiCompatModels($client, $apiKey, 'https://api.groq.com/openai/v1/models'),
                self::Ollama     => $this->fetchOllamaModels($client, $apiUrl ?: 'http://localhost:11434'),
                self::XAI        => $this->fetchOpenAiCompatModels($client, $apiKey, 'https://api.x.ai/v1/models'),
                self::Mistral    => $this->fetchOpenAiCompatModels($client, $apiKey, 'https://api.mistral.ai/v1/models'),
                self::DeepSeek   => $this->fetchOpenAiCompatModels($client, $apiKey, 'https://api.deepseek.com/models'),
                self::OpenRouter => $this->fetchOpenAiCompatModels($client, $apiKey, 'https://openrouter.ai/api/v1/models'),
                self::Azure      => $this->fetchAzureModels($client, $apiKey, $apiUrl),
                self::Custom     => $this->fetchCustomModels($client, $apiKey, $apiUrl),
            };
        } catch (\Exception $e) {
            report($e);

            throw $e;
        }
    }

    /**
     * Fetch models from a user-supplied OpenAI-compatible endpoint.
     * Returns an empty list (instead of throwing) when no api_url is configured —
     * the user can still add model IDs manually via the "+ Add" UI.
     */
    private function fetchCustomModels(Client $client, ?string $apiKey, ?string $apiUrl): array
    {
        if (! $apiUrl) {
            return [];
        }

        return $this->fetchOpenAiCompatModels($client, $apiKey, rtrim($apiUrl, '/').'/models');
    }

    private function fetchOpenAiModels(Client $client, ?string $apiKey): array
    {
        $response = $client->get('https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $models = array_column($data['data'] ?? [], 'id');
        sort($models);

        return $models;
    }

    private function fetchGeminiModels(Client $client, ?string $apiKey): array
    {
        $response = $client->get('https://generativelanguage.googleapis.com/v1beta/models', [
            'query' => ['key' => $apiKey],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $models = [];

        foreach ($data['models'] ?? [] as $model) {
            $name = $model['name'] ?? '';
            $models[] = str_replace('models/', '', $name);
        }

        sort($models);

        return $models;
    }

    private function fetchOpenAiCompatModels(Client $client, ?string $apiKey, string $url): array
    {
        $response = $client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $models = array_map(
            fn ($id) => ltrim((string) $id, '~'),
            array_column($data['data'] ?? [], 'id')
        );
        sort($models);

        return $models;
    }

    private function fetchOllamaModels(Client $client, string $baseUrl): array
    {
        $response = $client->get(rtrim($baseUrl, '/').'/api/tags');

        $data = json_decode($response->getBody()->getContents(), true);
        $models = array_column($data['models'] ?? [], 'name');
        sort($models);

        return $models;
    }

    private function fetchAnthropicModels(Client $client, ?string $apiKey): array
    {
        $response = $client->get('https://api.anthropic.com/v1/models', [
            'headers' => [
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        $models = array_column($data['data'] ?? [], 'id');
        sort($models);

        return $models;
    }

    private function fetchAzureModels(Client $client, ?string $apiKey, ?string $apiUrl): array
    {
        if (! $apiUrl) {
            return [];
        }

        try {
            $url = rtrim($apiUrl, '/').'/openai/models?api-version=2024-10-21';

            $response = $client->get($url, [
                'headers' => [
                    'api-key'      => $apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $models = array_column($data['data'] ?? [], 'id');
            sort($models);

            return $models;
        } catch (\Exception $e) {
            return [];
        }
    }
}

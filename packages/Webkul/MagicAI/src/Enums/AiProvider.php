<?php

namespace Webkul\MagicAI\Enums;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;
use Webkul\Webhook\Validators\SafeWebhookUrl;

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
    case Concentrate = 'concentrate';
    case Custom = 'custom';

    public function toLab(): Lab
    {
        return match ($this) {
            self::OpenAI      => Lab::OpenAI,
            self::Anthropic   => Lab::Anthropic,
            self::Gemini      => Lab::Gemini,
            self::Groq        => Lab::Groq,
            self::Ollama      => Lab::Ollama,
            self::XAI         => Lab::xAI,
            self::Mistral     => Lab::Mistral,
            self::DeepSeek    => Lab::DeepSeek,
            self::Azure       => Lab::Azure,
            self::OpenRouter  => Lab::OpenRouter,
            self::Concentrate => Lab::OpenAICompatible,
            // Custom providers (Cerebras, Together, Fireworks, Perplexity,
            // DeepInfra, etc.) implement OpenAI's /chat/completions endpoint,
            // which laravel/ai's dedicated OpenAI-compatible driver speaks.
            self::Custom => Lab::OpenAICompatible,
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
            self::OpenAI      => 'https://api.openai.com/v1',
            self::Anthropic   => 'https://api.anthropic.com/v1',
            self::Gemini      => 'https://generativelanguage.googleapis.com/v1beta',
            self::Groq        => 'https://api.groq.com/openai/v1',
            self::Ollama      => 'http://localhost:11434',
            self::XAI         => 'https://api.x.ai/v1',
            self::Mistral     => 'https://api.mistral.ai/v1',
            self::DeepSeek    => 'https://api.deepseek.com',
            self::Azure       => '',
            self::OpenRouter  => 'https://openrouter.ai/api/v1',
            self::Concentrate => 'https://api.concentrate.ai/v1',
            self::Custom      => '',
        };
    }

    public function configKey(): string
    {
        return match ($this) {
            self::OpenRouter  => 'openrouter',
            self::Concentrate => 'openai-compatible',
            // Custom routes through laravel/ai's OpenAI-compatible driver, so
            // its api_url override must land in that config namespace.
            self::Custom => 'openai-compatible',
            default      => $this->value,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OpenAI      => 'OpenAI',
            self::Anthropic   => 'Anthropic',
            self::Gemini      => 'Google Gemini',
            self::Groq        => 'Groq',
            self::Ollama      => 'Ollama',
            self::XAI         => 'xAI (Grok)',
            self::Mistral     => 'Mistral',
            self::DeepSeek    => 'DeepSeek',
            self::Azure       => 'Azure OpenAI',
            self::OpenRouter  => 'OpenRouter',
            self::Concentrate => 'Concentrate AI',
            self::Custom      => 'Custom (OpenAI-compatible)',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $provider): array => [
            'title' => $provider->label(),
            'value' => $provider->value,
        ], self::cases());
    }

    /**
     * Fetch available models from the provider API.
     */
    public function fetchModels(?string $apiKey, ?string $apiUrl = null, ?Client $client = null): array
    {
        $client ??= new Client(['timeout' => 15]);

        try {
            return match ($this) {
                self::OpenAI                                                                              => $this->fetchOpenAiModels($client, $apiKey, $apiUrl),
                self::Anthropic                                                                           => $this->fetchAnthropicModels($client, $apiKey, $apiUrl),
                self::Gemini                                                                              => $this->fetchGeminiModels($client, $apiKey, $apiUrl),
                self::Ollama                                                                              => $this->fetchOllamaModels($client, $apiUrl ?: $this->defaultUrl()),
                self::Azure                                                                               => $this->fetchAzureModels($client, $apiKey, $apiUrl),
                self::Custom                                                                              => $this->discoverModels($apiKey, $apiUrl, $client)['models'],
                self::Groq, self::XAI, self::Mistral, self::DeepSeek, self::OpenRouter, self::Concentrate => $this->fetchOpenAiCompatModels(
                    $client,
                    $apiKey,
                    $this->modelsUrl($apiUrl),
                    harden: (bool) $apiUrl,
                ),
            };
        } catch (RequestException $e) {
            report($e);

            throw new \RuntimeException($this->describeFetchFailure($e), previous: $e);
        } catch (\Exception $e) {
            report($e);

            throw $e;
        }
    }

    /**
     * The model-listing endpoint for this provider: the platform's own base URL
     * when one is configured — a proxy or regional endpoint must be discovered
     * against the same host generation will use — otherwise the provider default.
     */
    private function modelsUrl(?string $apiUrl): string
    {
        return rtrim($apiUrl ?: $this->defaultUrl(), '/').'/models';
    }

    /**
     * Fetch the models of an OpenAI-compatible endpoint along with the base URL
     * that answered, which the caller stores so generation targets the same base.
     *
     * @return array{models: list<string>, api_url: string}
     */
    public function discoverModels(?string $apiKey, ?string $apiUrl = null, ?Client $client = null): array
    {
        $client ??= new Client(['timeout' => 15]);

        if ($this !== self::Custom) {
            return [
                'models'  => $this->fetchModels($apiKey, $apiUrl, $client),
                'api_url' => (string) $apiUrl,
            ];
        }

        if (! $apiUrl) {
            return ['models' => [], 'api_url' => ''];
        }

        $failure = null;

        foreach ($this->customBaseUrls($apiUrl) as $base) {
            try {
                return [
                    'models'  => $this->fetchOpenAiCompatModels($client, $apiKey, $base.'/models', harden: true),
                    'api_url' => $base,
                ];
            } catch (RequestException $e) {
                report($e);

                $failure ??= new \RuntimeException($this->describeFetchFailure($e), previous: $e);
            }
        }

        throw $failure ?? new \RuntimeException(trans('admin::app.configuration.platform.message.fetch-models-fail'));
    }

    /**
     * OpenAI-compatible bases almost always carry a version segment; a base typed
     * without one still discovers models, so try the versioned path as a fallback.
     *
     * @return list<string>
     */
    private function customBaseUrls(string $apiUrl): array
    {
        $base = rtrim($apiUrl, '/');
        $path = (string) parse_url($base, PHP_URL_PATH);

        if (preg_match('#/v\d+[a-z0-9]*$#i', $path)) {
            return [$base];
        }

        return [$base, $base.'/v1'];
    }

    /**
     * Turn a transport failure into a short, readable message: upstream bodies are
     * often full HTML error pages that would otherwise be shown verbatim in the UI.
     */
    private function describeFetchFailure(RequestException $e): string
    {
        $host = $e->getRequest()->getUri()->getHost();
        $response = $e->getResponse();

        if (! $response) {
            return trans('admin::app.configuration.platform.message.fetch-models-unreachable', ['host' => $host]);
        }

        $decoded = json_decode((string) $response->getBody(), true);

        $detail = is_array($decoded)
            ? (string) ($decoded['error']['message'] ?? $decoded['message'] ?? '')
            : '';

        return trans('admin::app.configuration.platform.message.fetch-models-http-error', [
            'status' => $response->getStatusCode(),
            'host'   => $host,
        ]).($detail === '' ? '' : ' '.Str::limit($detail, 160));
    }

    private function fetchOpenAiModels(Client $client, ?string $apiKey, ?string $apiUrl = null): array
    {
        $url = $this->modelsUrl($apiUrl);

        $response = $client->get($url, $this->discoveryOptions($url, $apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]));

        $data = json_decode($response->getBody()->getContents(), true);
        $models = array_column($data['data'] ?? [], 'id');
        sort($models);

        return $models;
    }

    private function fetchGeminiModels(Client $client, ?string $apiKey, ?string $apiUrl = null): array
    {
        $url = $this->modelsUrl($apiUrl);

        $response = $client->get($url, $this->discoveryOptions($url, $apiUrl, [
            'query' => ['key' => $apiKey],
        ]));

        $data = json_decode($response->getBody()->getContents(), true);
        $models = [];

        foreach ($data['models'] ?? [] as $model) {
            $name = $model['name'] ?? '';
            $models[] = str_replace('models/', '', $name);
        }

        sort($models);

        return $models;
    }

    /**
     * Discovery options for a provider whose base url the platform may override.
     * A platform-supplied host is pinned and barred from redirecting, so a
     * validated url cannot pivot to an internal one between check and fetch.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function discoveryOptions(string $url, ?string $apiUrl, array $options): array
    {
        return $apiUrl ? $this->ssrfGuardedOptions($url, $options) : $options;
    }

    private function fetchOpenAiCompatModels(Client $client, ?string $apiKey, string $url, bool $harden = false): array
    {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type'  => 'application/json',
            ],
        ];

        $response = $client->get($url, $harden ? $this->ssrfGuardedOptions($url, $options) : $options);

        $data = json_decode($response->getBody()->getContents(), true);
        $models = array_map(
            fn ($id): string => ltrim((string) $id, '~'),
            array_column($data['data'] ?? [], 'id')
        );
        sort($models);

        return $models;
    }

    private function fetchOllamaModels(Client $client, string $baseUrl): array
    {
        $url = rtrim($baseUrl, '/').'/api/tags';

        $response = $client->get($url, $this->ssrfGuardedOptions($url));

        $data = json_decode($response->getBody()->getContents(), true);
        $models = array_column($data['models'] ?? [], 'name');
        sort($models);

        return $models;
    }

    /**
     * Forbid redirect following, and — when the URL validates as public —
     * additionally pin the host to the address that validation resolved, so a
     * user-supplied model-discovery URL cannot pivot to an internal host via a
     * 30x redirect or DNS rebinding. A URL that fails validation gets the
     * redirect guard alone; the caller is expected to have rejected it already.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function ssrfGuardedOptions(string $url, array $options = []): array
    {
        return array_merge($options, SafeWebhookUrl::httpOptions($url));
    }

    private function fetchAnthropicModels(Client $client, ?string $apiKey, ?string $apiUrl = null): array
    {
        $url = $this->modelsUrl($apiUrl);

        $response = $client->get($url, $this->discoveryOptions($url, $apiUrl, [
            'headers' => [
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ],
        ]));

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

        $base = rtrim($apiUrl, '/');

        $failure = null;

        foreach (["{$base}/openai/v1/models?api-version=preview", "{$base}/openai/models?api-version=2024-10-21"] as $url) {
            try {
                $response = $client->get($url, $this->ssrfGuardedOptions($url, [
                    'headers' => [
                        'api-key'      => $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                ]));

                $data = json_decode($response->getBody()->getContents(), true);
                $models = array_column($data['data'] ?? [], 'id');
                sort($models);

                return $models;
            } catch (RequestException $e) {
                report($e);

                $failure ??= $e;
            }
        }

        throw $failure;
    }
}

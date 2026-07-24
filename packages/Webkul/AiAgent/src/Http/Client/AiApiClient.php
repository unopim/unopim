<?php

namespace Webkul\AiAgent\Http\Client;

use Illuminate\Support\Facades\Log;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Exceptions\ApiException;
use Webkul\AiAgent\Services\TokenEstimator;
use Webkul\Webhook\Validators\SafeWebhookUrl;

/**
 * cURL-based HTTP client for AI provider APIs.
 *
 * Unopim connectors use native cURL — NOT Guzzle or Laravel HTTP facade.
 */
class AiApiClient
{
    /**
     * Conservative fallback context window when the model is unknown.
     */
    public const DEFAULT_CONTEXT_WINDOW = 128000;

    /**
     * Conservative context window sizes keyed by lowercase model-name prefix.
     * Overridable / extendable via config('ai-agent.token_estimation.context_windows').
     *
     * @var array<string, int>
     */
    protected const CONTEXT_WINDOWS = [
        'claude'      => 200000,
        'gpt-3.5'     => 16385,
        'gpt-4o'      => 128000,
        'gpt-4-turbo' => 128000,
        'gpt-4.1'     => 128000,
        'gpt-4'       => 8192,
    ];

    protected string $baseUrl = '';

    protected string $apiKey = '';

    protected string $model = '';

    protected string $provider = 'openai';

    protected TokenEstimator $tokenEstimator;

    /**
     * Create the client. The estimator is container-injected when available.
     */
    public function __construct(?TokenEstimator $tokenEstimator = null)
    {
        $this->tokenEstimator = $tokenEstimator ?? new TokenEstimator;
    }

    /**
     * Configure the client from a CredentialConfig DTO.
     */
    public function configure(CredentialConfig $config): static
    {
        $this->baseUrl = preg_replace('#/v\d+/?$#', '', rtrim($config->apiUrl, '/'));
        $this->apiKey = $config->apiKey;
        $this->model = $config->model;
        $this->provider = $config->provider;

        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Send a chat completion request to the AI provider.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, tokensUsed: int, cachedTokens: int, raw: array<mixed>}
     *
     * @throws ApiException
     * @throws \RuntimeException When the request exceeds the model context window and cannot be trimmed
     */
    public function chat(array $messages, int $maxTokens = 4096, float $temperature = 0.7): array
    {
        $messages = $this->enforceContextWindow($messages, $maxTokens);

        $endpoint = $this->getChatEndpoint();

        $body = $this->buildChatBody($messages, $maxTokens, $temperature);

        $response = $this->execute('POST', $endpoint, $body);

        return $this->parseChatResponse($response);
    }

    /**
     * Test connection to the AI provider.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array
    {
        try {
            $this->chat(
                messages: [['role' => 'user', 'content' => 'Hello']],
                maxTokens: 10,
            );

            return ['success' => true, 'message' => trans('ai-agent::app.common.connection-verified')];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Execute a cURL request.
     *
     * @param  array<mixed>|null  $data
     * @return array<mixed>
     *
     * @throws ApiException
     */
    protected function execute(string $method, string $url, ?array $data = null): array
    {
        // Pin the connection to the validated IP (CURLOPT_RESOLVE) and disable
        // redirects, so a rebinding host cannot re-resolve to an internal address
        // between validation and the actual request (SSRF TOCTOU).
        $safeOptions = SafeWebhookUrl::httpOptions($url);

        throw_unless(
            isset($safeOptions['curl'][CURLOPT_RESOLVE]),
            ApiException::class,
            trans('admin::app.configuration.platform.message.unsafe-api-url'),
            422
        );

        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer '.$this->apiKey,
        ];

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RESOLVE        => $safeOptions['curl'][CURLOPT_RESOLVE],
        ];

        if ($method === 'POST' && $data !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);

        curl_close($ch);

        throw_if($curlErrno !== 0, ApiException::class, 'cURL error: '.$curlError, $curlErrno);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $decoded['error']['message'] ?? $decoded['message'] ?? trans('ai-agent::app.common.unknown-api-error');

            throw new ApiException('AI API error ('.$httpCode.'): '.$errorMsg, $httpCode);
        }

        return $decoded ?? [];
    }

    /**
     * Get the chat completion endpoint URL for the configured provider.
     */
    protected function getChatEndpoint(): string
    {
        return match ($this->provider) {
            'anthropic' => $this->baseUrl.'/v1/messages',
            default     => $this->baseUrl.'/v1/chat/completions',
        };
    }

    /**
     * Build the request body for a chat completion.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<string, mixed>
     */
    protected function buildChatBody(array $messages, int $maxTokens, float $temperature): array
    {
        if ($this->provider === 'anthropic') {
            $body = [
                'model'      => $this->model,
                'max_tokens' => $maxTokens,
                'messages'   => $this->convertToAnthropicFormat($messages),
            ];

            $system = $this->extractSystemMessage($messages);

            if ($system !== '') {
                // Mark the static system prefix for Anthropic prompt caching (issue #421).
                // The client sends no tool definitions, so the system block is the
                // only cacheable static prefix.
                $body['system'] = [
                    [
                        'type'          => 'text',
                        'text'          => $system,
                        'cache_control' => ['type' => 'ephemeral'],
                    ],
                ];
            }

            return $body;
        }

        $body = [
            'model'                 => $this->model,
            'messages'              => $messages,
            'max_completion_tokens' => $maxTokens,
        ];

        // Reasoning models (o-series, gpt-5*) reject `temperature` — only the default is allowed.
        if (! preg_match('/^chat-latest|^o[1-9]|^o[1-9]-|^gpt-5/i', $this->model)) {
            $body['temperature'] = $temperature;
        }

        return $body;
    }

    /**
     * Parse the chat response based on provider format.
     *
     * `cachedTokens` reports how many input tokens went through the provider's
     * prompt cache: cache reads + cache writes for Anthropic, cache hits for
     * OpenAI (`prompt_tokens_details.cached_tokens`).
     *
     * @param  array<mixed>  $response
     * @return array{content: string, tokensUsed: int, cachedTokens: int, raw: array<mixed>}
     */
    protected function parseChatResponse(array $response): array
    {
        return match ($this->provider) {
            // Anthropic's input_tokens EXCLUDES cached tokens, which are
            // reported separately — add them so budgets see real usage.
            'anthropic' => [
                'content'      => $response['content'][0]['text'] ?? '',
                'tokensUsed'   => ($response['usage']['input_tokens'] ?? 0)
                    + ($response['usage']['output_tokens'] ?? 0)
                    + ($response['usage']['cache_read_input_tokens'] ?? 0)
                    + ($response['usage']['cache_creation_input_tokens'] ?? 0),
                'cachedTokens' => ($response['usage']['cache_read_input_tokens'] ?? 0) + ($response['usage']['cache_creation_input_tokens'] ?? 0),
                'raw'          => $response,
            ],
            default => [
                'content'      => $response['choices'][0]['message']['content'] ?? '',
                'tokensUsed'   => $response['usage']['total_tokens'] ?? 0,
                'cachedTokens' => $response['usage']['prompt_tokens_details']['cached_tokens'] ?? 0,
                'raw'          => $response,
            ],
        };
    }

    /**
     * Convert messages to Anthropic format (system prompt separated).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<int, array{role: string, content: string}>
     */
    protected function convertToAnthropicFormat(array $messages): array
    {
        return array_values(array_filter($messages, fn (array $m): bool => $m['role'] !== 'system'));
    }

    /**
     * Extract combined system message from the messages array.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    protected function extractSystemMessage(array $messages): string
    {
        $systemParts = array_filter($messages, fn (array $m): bool => $m['role'] === 'system');

        return implode("\n\n", array_column($systemParts, 'content'));
    }

    /**
     * Pre-flight token guard (issue #423): estimate the payload before the
     * HTTP call and trim oldest history / largest context blocks when the
     * estimate exceeds the model context window. Only numeric counts are
     * logged — never prompt content or credentials.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<int, array{role: string, content: string}>
     *
     * @throws \RuntimeException When the request cannot be trimmed to fit
     */
    protected function enforceContextWindow(array $messages, int $maxTokens): array
    {
        $window = $this->contextWindowFor($this->model);

        $limit = max(
            $window - $maxTokens,
            (int) config('ai-agent.token_estimation.min_input_window', 1024),
        );

        $estimates = array_map($this->tokenEstimator->estimateMessage(...), $messages);
        $estimate = array_sum($estimates);

        Log::debug('AI Agent pre-flight token estimate', [
            'provider'               => $this->provider,
            'model'                  => $this->model,
            'estimated_input_tokens' => $estimate,
            'input_token_limit'      => $limit,
            'context_window'         => $window,
            'max_output_tokens'      => $maxTokens,
            'message_count'          => count($messages),
        ]);

        if ($estimate <= $limit) {
            return $messages;
        }

        Log::warning('AI Agent request exceeds the model context window — trimming context', [
            'provider'               => $this->provider,
            'model'                  => $this->model,
            'estimated_input_tokens' => $estimate,
            'input_token_limit'      => $limit,
            'message_count'          => count($messages),
        ]);

        $lastIndex = array_key_last($messages);

        // Pass 1: drop the oldest non-system history, never the final message.
        foreach (array_keys($messages) as $index) {
            if ($index === $lastIndex) {
                continue;
            }
            if (($messages[$index]['role'] ?? '') === 'system') {
                continue;
            }
            $estimate -= $estimates[$index];
            unset($messages[$index]);

            if ($estimate <= $limit) {
                return $this->dropLeadingAssistantMessages(array_values($messages));
            }
        }
        $firstSystemIndex = array_find_key($messages, fn ($message): bool => ($message['role'] ?? '') === 'system');

        $trimmable = [];

        foreach (array_keys($messages) as $index) {
            if ($index === $lastIndex) {
                continue;
            }
            if ($index === $firstSystemIndex) {
                continue;
            }
            $trimmable[$index] = $estimates[$index];
        }

        arsort($trimmable);

        foreach (array_keys($trimmable) as $index) {
            $estimate -= $estimates[$index];
            unset($messages[$index]);

            if ($estimate <= $limit) {
                return $this->dropLeadingAssistantMessages(array_values($messages));
            }
        }

        Log::warning('AI request is too large and could not be trimmed', [
            'model'                  => $this->model,
            'estimated_input_tokens' => $estimate,
            'input_token_limit'      => $limit,
        ]);

        throw new \RuntimeException(trans('ai-agent::app.common.error-request-too-large'));
    }

    /**
     * Drop leading assistant messages so the first non-system message is a
     * user turn — Anthropic's Messages API rejects assistant-first lists,
     * which trimming the oldest user turn can otherwise produce.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    protected function dropLeadingAssistantMessages(array $messages): array
    {
        foreach ($messages as $index => $message) {
            $role = $message['role'] ?? '';

            if ($role === 'system') {
                continue;
            }

            if ($role === 'assistant') {
                unset($messages[$index]);

                continue;
            }

            break;
        }

        return array_values($messages);
    }

    /**
     * Resolve the context window for a model by longest lowercase prefix match,
     * merging config overrides over the built-in conservative defaults.
     */
    protected function contextWindowFor(string $model): int
    {
        $map = array_merge(
            self::CONTEXT_WINDOWS,
            (array) config('ai-agent.token_estimation.context_windows', []),
        );

        uksort($map, fn ($a, $b): int => strlen((string) $b) <=> strlen((string) $a));

        $model = strtolower($model);

        foreach ($map as $prefix => $window) {
            if (str_starts_with($model, strtolower((string) $prefix))) {
                return (int) $window;
            }
        }

        return (int) config('ai-agent.token_estimation.default_context_window', self::DEFAULT_CONTEXT_WINDOW);
    }
}

<?php

namespace Webkul\AiAgent\Http\Client;

use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Exceptions\ApiException;

/**
 * cURL-based HTTP client for AI provider APIs.
 *
 * Unopim connectors use native cURL — NOT Guzzle or Laravel HTTP facade.
 */
class AiApiClient
{
    protected string $baseUrl = '';

    protected string $apiKey = '';

    protected string $model = '';

    protected string $provider = 'openai';

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

    /**
     * Get the configured provider name.
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Get the configured model name.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Send a chat completion request to the AI provider.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, tokensUsed: int, raw: array<mixed>}
     *
     * @throws ApiException
     */
    public function chat(array $messages, int $maxTokens = 4096, float $temperature = 0.7): array
    {
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
            $result = $this->chat(
                messages: [['role' => 'user', 'content' => 'Hello']],
                maxTokens: 10,
            );

            return ['success' => true, 'message' => 'Connection verified.'];
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

        if ($curlErrno) {
            throw new ApiException('cURL error: '.$curlError, $curlErrno);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $decoded['error']['message'] ?? $decoded['message'] ?? 'Unknown API error';

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
            return [
                'model'      => $this->model,
                'max_tokens' => $maxTokens,
                'messages'   => $this->convertToAnthropicFormat($messages),
                'system'     => $this->extractSystemMessage($messages),
            ];
        }

        $body = [
            'model'                 => $this->model,
            'messages'              => $messages,
            'max_completion_tokens' => $maxTokens,
        ];

        // Reasoning models (o-series, gpt-5*) reject `temperature` — only the default is allowed.
        if (! preg_match('/^o[1-9]|^o[1-9]-|^gpt-5/i', $this->model)) {
            $body['temperature'] = $temperature;
        }

        return $body;
    }

    /**
     * Parse the chat response based on provider format.
     *
     * @param  array<mixed>  $response
     * @return array{content: string, tokensUsed: int, raw: array<mixed>}
     */
    protected function parseChatResponse(array $response): array
    {
        return match ($this->provider) {
            'anthropic' => [
                'content'    => $response['content'][0]['text'] ?? '',
                'tokensUsed' => ($response['usage']['input_tokens'] ?? 0) + ($response['usage']['output_tokens'] ?? 0),
                'raw'        => $response,
            ],
            default => [
                'content'    => $response['choices'][0]['message']['content'] ?? '',
                'tokensUsed' => $response['usage']['total_tokens'] ?? 0,
                'raw'        => $response,
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
        return array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system'));
    }

    /**
     * Extract combined system message from the messages array.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    protected function extractSystemMessage(array $messages): string
    {
        $systemParts = array_filter($messages, fn ($m) => $m['role'] === 'system');

        return implode("\n\n", array_column($systemParts, 'content'));
    }
}

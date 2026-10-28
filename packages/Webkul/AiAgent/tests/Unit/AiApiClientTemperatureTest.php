<?php

use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Http\Client\AiApiClient;

/**
 * Test double: captures the request body instead of performing a cURL call.
 */
class AiApiClientTemperatureDouble extends AiApiClient
{
    /** @var array<string, mixed> */
    public array $capturedBody = [];

    protected function execute(string $method, string $url, ?array $data = null): array
    {
        $this->capturedBody = $data ?? [];

        return ['choices' => [['message' => ['content' => 'ok']]], 'usage' => []];
    }
}

function sendTemperatureTestChat(string $model): array
{
    $client = new AiApiClientTemperatureDouble;

    $client->configure(new CredentialConfig(
        id: 1,
        label: 'Test credential',
        provider: 'openai',
        apiUrl: 'https://api.example.test',
        apiKey: 'test-key',
        model: $model,
    ));

    $client->chat([['role' => 'user', 'content' => 'Hello']], temperature: 0.42);

    return $client->capturedBody;
}

describe('AiApiClient temperature for OpenAI models', function () {

    it('omits temperature for reasoning models', function (string $model) {
        expect(sendTemperatureTestChat($model))->not->toHaveKey('temperature');
    })->with([
        'o1-mini',
        'o3',
        'gpt-5.2-pro',
        'gpt-6',
        'gpt-6.1-terra',
        'GPT-6-mini',
        'gpt-7',
        'gpt-10-mini',
    ]);

    it('sends temperature for models that accept it', function (string $model) {
        expect(sendTemperatureTestChat($model))->toHaveKey('temperature', 0.42);
    })->with([
        'gpt-4o',
        'gpt-4.1-mini',
        'gpt-3.5-turbo',
        'gpt-35-turbo',
        'gpt-5-chat-latest',
    ]);
});

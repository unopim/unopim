<?php

namespace Webkul\MagicAI\Validator;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use OpenAI\ValueObjects\Transporter\BaseUri;
use Webkul\MagicAI\Contracts\Validator\ConfigValidator;
use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Services\Gemini;
use Webkul\MagicAI\Services\Groq;
use Webkul\MagicAI\Services\Ollama;
use Webkul\MagicAI\Services\OpenAI;

class MagicAICredentialValidator implements ConfigValidator
{
    const DEFAULT_MODELS = [
        ['id' => 'llama3', 'label' => 'llama3'],
        ['id' => 'mistral', 'label' => 'mistral'],
    ];

    const MODEL_ENDPOINTS = [
        MagicAI::MAGIC_OPEN_AI   => 'v1/models',
        MagicAI::MAGIC_GROQ_AI   => 'openai/v1/models',
        MagicAI::MAGIC_GEMINI_AI => 'v1beta/models',
    ];

    protected ?string $baseUri = null;

    protected Client $client;

    public function __construct()
    {
        $this->client = new Client;
    }

    public function validate(array $credentials, array $options = []): array
    {
        $credentials = $credentials['general']['magic_ai']['settings'];

        if (! str_starts_with($credentials['api_domain'], 'http')) {
            $credentials['api_domain'] = 'https://'.$credentials['api_domain'];
        }

        $rules = [
            'enabled'       => 'required|in:0,1',
            'ai_platform'   => 'required|in:openai,ollama,groq,gemini',
            'api_key'       => 'required|string|min:10',
            'organization'  => 'nullable|string',
            'api_domain'    => 'required|url',
            'api_model'     => 'nullable|string',
        ];

        $validator = Validator::make($credentials, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if ($credentials['ai_platform'] === 'ollama') {
            return Ollama::formatModelsResponse(['models' => self::DEFAULT_MODELS]);
        }

        try {
            if (preg_match('/^\*+$/', $credentials['api_key'])) {
                $original = core()->getConfigData('general.magic_ai.settings.api_key');
                if (strlen($credentials['api_key']) === strlen($original)) {
                    $credentials['api_key'] = $original;
                }
            }

            $this->baseUri = $credentials['api_domain'] ?? null;
            $baseUri = BaseUri::from($this->baseUri ?: 'https://api.openai.com')->toString();

            $platform = $credentials['ai_platform'];
            $modelEndpoint = self::MODEL_ENDPOINTS[$platform] ?? null;

            if (! $modelEndpoint) {
                return self::DEFAULT_MODELS;
            }

            $url = rtrim($baseUri, '/').'/'.ltrim($modelEndpoint, '/');

            $requestOptions = [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ];

            if ($platform === MagicAI::MAGIC_GEMINI_AI) {
                $url .= '?key='.$credentials['api_key'];
            } else {
                $requestOptions['headers']['Authorization'] = 'Bearer '.$credentials['api_key'];
            }

            $response = $this->client->get($url, $requestOptions);
            $data = json_decode($response->getBody(), true);

            switch ($platform) {
                case MagicAI::MAGIC_GEMINI_AI:
                    $formattedModels = Gemini::formatModelsResponse($data);
                    break;
                case MagicAI::MAGIC_OPEN_AI:
                    $formattedModels = OpenAI::formatModelsResponse($data);
                    break;
                case MagicAI::MAGIC_GROQ_AI:
                    $formattedModels = Groq::formatModelsResponse($data);
                    break;
                default:
                    $formattedModels = self::DEFAULT_MODELS;
            }

            return $formattedModels ?: self::DEFAULT_MODELS;
        } catch (\Exception $e) {
            report($e);
            throw ValidationException::withMessages([
                'api_key' => ['Invalid credentials or unable to reach AI platform.'],
            ]);
        }
    }
}

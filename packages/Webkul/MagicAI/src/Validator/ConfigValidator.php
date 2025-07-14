<?php

namespace Webkul\MagicAI\Validator;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;
use Webkul\MagicAI\MagicAI;
use OpenAI\ValueObjects\Transporter\BaseUri;
use Webkul\MagicAI\Contracts\Validator\ConfigValidator as ConfigValidatorContract;

class ConfigValidator implements ConfigValidatorContract
{
    const DEFAULT_MODELS = [
        ['id' => 'llama3', 'label' => 'llama3'],
        ['id' => 'mistral', 'label' => 'mistral'],
    ];

    const MODEL_ENDPOINTS = [
        MagicAI::MAGIC_OPEN_AI => 'v1/models',
        MagicAI::MAGIC_GROQ_AI => 'openai/v1/models',
    ];

    protected ?string $baseUri = null;
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function validate(array $credentials,array $options = []): array
    {

        if (! str_starts_with($credentials['api_domain'], 'http')) {
            $credentials['api_domain'] = 'https://' . $credentials['api_domain'];
        }

        $rules = [
            'enabled'       => 'required|in:0,1',
            'ai_platform'  => 'required|in:openai,ollama',
            'api_key'       => 'required_if:api_platform,openai|string|min:10',
            'organization'  => 'nullable|string',
            'api_domain'    => 'required|url',
            'api_model'     => 'nullable|string',
        ];

        $validator = Validator::make($credentials, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Short-circuit if platform is ollama
        if ($credentials['ai_platform'] === 'ollama') {
            return self::DEFAULT_MODELS;
        }

        try {
            if (preg_match('/^\*+$/', $credentials['api_key'])) {
                $original = core()->getConfigData('general.magic_ai.settings.api_key');

                if (strlen($credentials['api_key']) === strlen($original)) {
                    $credentials['api_key'] = $original;
                }
            }
            $this->baseUri = $credentials['api_domain'];
            $baseUri = BaseUri::from($this->baseUri ?: 'api.openai.com')->toString();

            $modelEndpoint = self::MODEL_ENDPOINTS[$credentials['api_platform'] ?? 'openai'];
            $response = $this->client->get($baseUri . $modelEndpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $credentials['api_key'],
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $body = $response->getBody();
            $data = json_decode($body, true);

            $formattedModels = [];

            foreach (($data['data'] ?? []) as $model) {
                $formattedModels[] = [
                    'id'    => $model['id'],
                    'label' => $model['id'],
                ];
            }

            return $formattedModels;
        } catch (\Exception $e) {
            report($e);
            throw ValidationException::withMessages([
                'api_key' => ['Invalid credentials or unable to reach AI platform.'],
            ]);
        }
    }
}

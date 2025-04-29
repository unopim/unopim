<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;
use OpenAI\ValueObjects\Transporter\BaseUri;
use Webkul\MagicAI\MagicAI;

class AIModel
{
    private static $instance;

    private $client;

    private $apiKey;

    private ?string $baseUri = null;

    const MODEL_ENDPOINTS = [
        MagicAI::MAGIC_OPEN_AI => 'v1/models',
        MagicAI::MAGIC_GROQ_AI => 'openai/v1/models',
    ];

    const DEFAULT_MODELS = [
        ['id' => 'gpt-4o'],
        ['id' => 'gpt-4o-mini'],
        ['id' => 'starling'],
        ['id' => 'gpt-3.5-turbo'],
        ['id' => 'mistral:7b'],
        ['id' => 'phi3.5'],
        ['id' => 'starling-lm:7b'],
        ['id' => 'llama2:13b'],
        ['id' => 'llama3.2:3b'],
        ['id' => 'llama3.2:1b'],
        ['id' => 'llama3.1:8b'],
        ['id' => 'llama3:8b'],
        ['id' => 'llama3:8b'],
        ['id' => 'qwen2.5:14b'],
        ['id' => 'qwen2.5:7b'],
        ['id' => 'qwen2.5:3b'],
        ['id' => 'qwen2.5:1.5b'],
        ['id' => 'qwen2.5:0.5b'],
        ['id' => 'orca-mini'],
        ['id' => 'vicuna:13b'],
        ['id' => 'vicuna:7b'],
        ['id' => 'llava:7b'],
    ];

    /**
     * AIModel constructor.
     */
    private function __construct()
    {
        $this->client = new Client;
        $this->setConfig();
    }

    /**
     * Sets OpenAI credentials.
     */
    public function setConfig(): void
    {
        $this->apiKey = core()->getConfigData('general.magic_ai.settings.api_key');
        $this->baseUri = core()->getConfigData('general.magic_ai.settings.api_domain');
    }

    /**
     * Gets the singleton instance of AIModel.
     */
    public static function getInstance(): AIModel
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Gets the list of models.
     */
    public static function getModels(): array
    {
        return self::getInstance()->getFormattedModelList();
    }

    /**
     * Gets the list of models.
     */
    public static function validate(): array
    {
        return self::getInstance()->validateCredential();
    }

    /**
     * Gets the list of models from the API.
     */
    private function getModelList(): array
    {
        $credentials = request()->all();

        $this->baseUri = $credentials['api_domain'] ?? $this->baseUri;

        $baseUri = BaseUri::from($this->baseUri ?: 'api.openai.com')->toString();
        $modelEndpoint = self::MODEL_ENDPOINTS[$credentials['api_platform'] ?? core()->getConfigData('general.magic_ai.settings.ai_platform')] ?? null;

        if (! $modelEndpoint || ! (bool) core()->getConfigData('general.magic_ai.settings.enabled')) {
            return self::DEFAULT_MODELS;
        }

        $this->apiKey = $credentials['api_key'] ?? $this->apiKey;

        try {
            $response = $this->client->get(sprintf('%s%s', $baseUri, $modelEndpoint), [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $body = $response->getBody();
            $data = json_decode($body, true);

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            throw $e;
            report($e);

            return [];
        }
    }

    /**
     * Validate the AI credential.
     */
    public function validateCredential()
    {
        $credentials = request()->all();

        if (isset($credentials['api_platform']) && $credentials['api_platform'] == 'ollama') {
            return self::DEFAULT_MODELS;
        }

        try {
            $this->baseUri = $credentials['api_domain'] ?? $this->baseUri;
            $baseUri = BaseUri::from($this->baseUri ?: 'api.openai.com')->toString();
            $modelEndpoint = self::MODEL_ENDPOINTS[$credentials['api_platform'] ?? core()->getConfigData('general.magic_ai.settings.ai_platform')] ?? null;
            $response = $this->client->get(sprintf('%s%s', $baseUri, $modelEndpoint), [
                'headers' => [
                    'Authorization' => 'Bearer '.$credentials['api_key'],
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
            throw $e;
            report($e);

            return [];
        }
    }

    /**
     * Formats the list of models.
     */
    private function getFormattedModelList(): array
    {
        $models = $this->getModelList();
        $formattedModels = [];

        foreach ($models as $model) {
            $formattedModels[] = [
                'id'    => $model['id'],
                'label' => $model['id'],
            ];
        }

        return $formattedModels;
    }

    /**
     * Gets the available models from the configuration.
     */
    public static function getAvailableModels(): array
    {
        $models = explode(',', core()->getConfigData('general.magic_ai.settings.api_model'));

        return array_map(function ($model) {
            return [
                'id'    => $model,
                'label' => $model,
            ];
        }, $models);
    }
}

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
    private function setConfig(): void
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
     * Gets the list of models from the API.
     */
    private function getModelList(): array
    {
        $baseUri = BaseUri::from($this->baseUri ?: 'api.openai.com')->toString();
        $modelEndpoint = self::MODEL_ENDPOINTS[core()->getConfigData('general.magic_ai.settings.ai_platform')] ?? null;

        if (! $modelEndpoint) {
            return [];
        }

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
            session()->flash('error', $e->getMessage());
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
                'title' => $model['id'],
                'value' => $model['id'],
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

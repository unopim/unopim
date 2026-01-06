<?php

namespace Webkul\MagicAI\Services;

use OpenAI\Laravel\Facades\OpenAI as BaseOpenAI;
use Webkul\MagicAI\Contracts\LLMModelInterface;

class OpenAI implements LLMModelInterface
{
    /**
     * New service instance.
     */
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected float $temperature,
        protected int $maxTokens,
        protected string $systemPrompt,
        protected bool $stream = false,
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
                    'role'    => 'system',
                    'content' => $this->systemPrompt,
                ],
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
        $payload = [
            'model'  => $this->model,
            'prompt' => $this->prompt,
            'n'      => intval($options['n'] ?? 1),
            'size'   => $options['size'],
        ];

        if ($this->model !== 'gpt-image-1.5' && $this->model !== 'gpt-image-1' && $this->model !== 'gpt-image-1-mini') {
            $payload['response_format'] = 'b64_json';
        }

        if (isset($options['quality']) && $this->model !== 'dall-e-2') {
            $payload['quality'] = $options['quality'];
        }

        $result = BaseOpenAI::images()->create($payload);

        $images = [];

        foreach ($result->data as $image) {
            $images[]['url'] = 'data:image/png;base64,'.$image->b64_json;
        }

        return $images;
    }

    /**
     * Format the models response for OpenAI.
     */
    public static function formatModelsResponse(array $data): array
    {
        $formattedModels = [];
        foreach (($data['data'] ?? []) as $model) {
            $formattedModels[] = [
                'id'    => $model['id'],
                'label' => $model['id'],
            ];
        }

        return $formattedModels;
    }
}

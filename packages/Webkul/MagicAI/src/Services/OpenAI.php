<?php

namespace Webkul\MagicAI\Services;

use OpenAI\Laravel\Facades\OpenAI as BaseOpenAI;

class OpenAI
{
    /**
     * New service instance.
     */
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected string $systemPrompt,
        protected float $temperature,
        protected bool $stream = false
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
        $extraParameters = [];

        if (isset($options['quality']) && $this->model !== 'dall-e-2') {
            $extraParameters['quality'] = $options['quality'];
        }

        $result = BaseOpenAI::images()->create(array_merge([
            'model'           => $this->model,
            'prompt'          => $this->prompt,
            'n'               => intval($options['n'] ?? 1),
            'size'            => $options['size'],
            'response_format' => 'b64_json',
        ], $extraParameters));

        $images = [];

        foreach ($result->data as $image) {
            $images[]['url'] = 'data:image/png;base64,'.$image->b64_json;
        }

        return $images;
    }
}

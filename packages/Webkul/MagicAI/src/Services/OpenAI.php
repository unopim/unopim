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
        if (in_array($this->model, ['dall-e-2', 'dall-e-3'], true)) {

            $extraParameters = [];

            if (isset($options['quality']) && $this->model === 'dall-e-3') {
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
                $images[] = [
                    'url' => 'data:image/png;base64,'.$image->b64_json,
                ];
            }

            return $images;
        }

        if (str_starts_with($this->model, 'gpt-image')) {

            $response = BaseOpenAI::responses()->create([
                'model' => $this->model,
                'input' => $this->prompt,
            ]);

            $images = [];

            foreach ($response->output as $output) {
                foreach ($output->content as $content) {
                    if ($content->type === 'output_image' && ! empty($content->image_base64)) {
                        $images[] = [
                            'url' => 'data:image/png;base64,'.$content->image_base64,
                        ];
                    }
                }
            }

            if (empty($images)) {
                throw new \RuntimeException('OpenAI did not return any image data.');
            }

            return $images;
        }

        throw new \RuntimeException("Unsupported OpenAI image model: {$this->model}");
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

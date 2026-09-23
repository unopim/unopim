<?php

namespace Webkul\MagicAI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;

/**
 * @extends Factory<MagicAIPlatform>
 */
class MagicAIPlatformFactory extends Factory
{
    protected $model = MagicAIPlatform::class;

    public function definition(): array
    {
        return [
            'label'      => fake()->words(2, true),
            'provider'   => AiProvider::OpenAI->value,
            'api_url'    => AiProvider::OpenAI->defaultUrl(),
            'api_key'    => 'sk-'.fake()->lexify('????????????????'),
            'models'     => 'gpt-4o-mini,gpt-4o',
            'extras'     => null,
            'is_default' => false,
            'status'     => true,
        ];
    }

    public function default(): self
    {
        return $this->state(fn (): array => ['is_default' => true, 'status' => true]);
    }

    public function disabled(): self
    {
        return $this->state(fn (): array => ['status' => false, 'is_default' => false]);
    }

    public function provider(AiProvider $provider): self
    {
        return $this->state(fn (): array => [
            'provider' => $provider->value,
            'api_url'  => $provider->defaultUrl(),
        ]);
    }
}

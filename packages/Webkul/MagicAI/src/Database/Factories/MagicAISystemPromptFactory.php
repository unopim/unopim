<?php

namespace Webkul\MagicAI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\MagicAI\Models\MagicAISystemPrompt;

/**
 * @extends Factory<MagicAISystemPrompt>
 */
class MagicAISystemPromptFactory extends Factory
{
    protected $model = MagicAISystemPrompt::class;

    public function definition()
    {
        return [
            'title'        => fake()->sentence,
            'tone'         => fake()->word,
            'max_tokens'   => fake()->numberBetween(50, 1000),
            'temperature'  => fake()->randomFloat(1, 0, 2),
            'is_enabled'   => fake()->boolean ? 1 : 0,
        ];
    }
}

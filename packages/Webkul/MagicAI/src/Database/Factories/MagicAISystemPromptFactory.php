<?php

namespace Webkul\MagicAI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\MagicAI\Models\MagicAISystemPrompt;

class MagicAISystemPromptFactory extends Factory
{
    protected $model = MagicAISystemPrompt::class;

    public function definition()
    {
        return [
            'title'        => $this->faker->sentence,
            'tone'         => $this->faker->word,
            'max_tokens'   => $this->faker->numberBetween(50, 1000),
            'temperature'  => $this->faker->randomFloat(1, 0, 2),
            'is_enabled'   => $this->faker->boolean ? 1 : 0,
        ];
    }
}

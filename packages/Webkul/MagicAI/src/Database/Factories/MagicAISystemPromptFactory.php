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
            'title'  => $this->faker->sentence,
            'tone'   => $this->faker->paragraph,
        ];
    }
}

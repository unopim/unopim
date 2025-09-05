<?php

namespace Webkul\MagicAI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\MagicAI\Models\MagicSystemPrompt;

class MagicSystemPromptFactory extends Factory
{
    protected $model = MagicSystemPrompt::class;

    public function definition()
    {
        return [
            'title'  => $this->faker->sentence,
            'tone'   => $this->faker->paragraph,
        ];
    }
}

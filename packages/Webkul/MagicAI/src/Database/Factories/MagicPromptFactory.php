<?php

namespace Webkul\MagicAI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\MagicAI\Models\MagicPrompt;

class MagicPromptFactory extends Factory
{
    protected $model = MagicPrompt::class;

    public function definition()
    {
        return [
            'title'  => $this->faker->sentence,
            'prompt' => $this->faker->paragraph,
            'type'   => $this->faker->randomElement(['product', 'category']),
        ];
    }
}

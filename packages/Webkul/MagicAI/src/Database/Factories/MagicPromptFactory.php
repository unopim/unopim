<?php

namespace Webkul\MagicAI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\MagicAI\Models\MagicAISystemPrompt;
use Webkul\MagicAI\Models\MagicPrompt;

/**
 * @extends Factory<MagicPrompt>
 */
class MagicPromptFactory extends Factory
{
    protected $model = MagicPrompt::class;

    public function definition()
    {
        return [
            'title'   => fake()->sentence,
            'prompt'  => fake()->paragraph,
            'type'    => fake()->randomElement(['product', 'category']),
            'purpose' => fake()->randomElement(['text_generation', 'image_generation']),
            'tone'    => MagicAISystemPrompt::inRandomOrder()->value('id')
                      ?? MagicAISystemPrompt::factory()->create()->id,
        ];
    }
}

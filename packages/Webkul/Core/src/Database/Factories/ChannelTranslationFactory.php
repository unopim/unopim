<?php

namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\ChannelTranslation;

class ChannelTranslationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ChannelTranslation::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'locale'   => 'en_US',
            'name'     => $this->faker->word,
        ];
    }
}

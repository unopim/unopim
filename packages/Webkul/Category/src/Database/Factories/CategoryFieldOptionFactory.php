<?php

namespace Webkul\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Category\Models\CategoryFieldOption;

class CategoryFieldOptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CategoryFieldOption::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code'         => $this->faker->regexify('/^[a-zA-Z]+[a-zA-Z0-9_]+$/'),
            'sort_order'   => $this->faker->randomDigit(),
        ];
    }
}

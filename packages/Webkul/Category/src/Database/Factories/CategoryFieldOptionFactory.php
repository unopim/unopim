<?php

declare(strict_types=1);

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
            'code'         => $this->faker->regexify('/^[a-zA-Z]+\w+$/'),
            'sort_order'   => $this->faker->randomDigit(),
        ];
    }
}

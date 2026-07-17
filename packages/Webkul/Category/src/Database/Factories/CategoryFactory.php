<?php

namespace Webkul\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Category\Models\Category;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * @var string[]
     */
    protected $states = [
        'inactive',
        'rtl',
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code'            => fake()->regexify('/^[a-zA-Z]+\w+$/'),
            'parent_id'       => Category::whereIsRoot()->first()->id,
            'additional_data' => [
                'locale_specific' => [
                    core()->getRequestedLocaleCode() => [
                        'name' => fake()->name,
                    ],
                ],
            ],
        ];
    }
}

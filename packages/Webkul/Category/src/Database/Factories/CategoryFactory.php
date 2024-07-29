<?php

namespace Webkul\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Category\Models\Category;

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
            'parent_id' => 1,
        ];
    }

    /**
     * Handle rtl state
     */
    public function rtl(): CategoryFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'direction' => 'rtl',
            ];
        });
    }
}

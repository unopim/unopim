<?php

namespace Webkul\AdminApi\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\AdminApi\Models\Apikey;

class ApiKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Apikey::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'            => $this->faker->name,
            'permission_type' => $this->faker->randomElement(['custom', 'all']),
        ];
    }
}

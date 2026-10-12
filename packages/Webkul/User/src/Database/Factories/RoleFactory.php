<?php

namespace Webkul\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\User\Models\Role;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'            => fake()->name,
            'permission_type' => fake()->randomElement(['custom', 'all']),
        ];
    }
}

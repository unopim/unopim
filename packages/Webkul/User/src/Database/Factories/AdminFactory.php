<?php

namespace Webkul\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class AdminFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name'         => $this->faker->name(),
            'email'        => $this->faker->unique()->email,
            'password'     => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'role_id'      => Role::first()?->id ?? Role::factory(),
            'status'       => 1,
            'ui_locale_id' => Locale::where('code', 'en_US')->first()?->id ?? Locale::factory()->state(['code' => 'en_US', 'status' => 1]),
            'image'        => null,
        ];
    }
}

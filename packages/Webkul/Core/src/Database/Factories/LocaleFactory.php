<?php

namespace Webkul\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Core\Models\Locale;

/**
 * @extends Factory<Locale>
 */
class LocaleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Locale::class;

    /**
     * @var array
     */
    protected $states = [];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        do {
            $languageCode = fake()->languageCode;
        } while (Locale::query()
            ->where('code', $languageCode)
            ->exists());

        return [
            'code'   => $languageCode,
            'status' => fake()->boolean ? 1 : 0,
        ];
    }
}

<?php

namespace Webkul\Measurement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Measurement\Models\MeasurementFamily;

/**
 * @extends Factory<MeasurementFamily>
 */
class MeasurementFamilyFactory extends Factory
{
    protected $model = MeasurementFamily::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code'          => 'family_'.fake()->unique()->regexify('[a-z]{5}[0-9]{3}'),
            'name'          => fake()->word(),
            'standard_unit' => 'meter',
            'symbol'        => 'm',
            'labels'        => [
                'en_US' => fake()->word(),
            ],
            'units' => [],
        ];
    }
}

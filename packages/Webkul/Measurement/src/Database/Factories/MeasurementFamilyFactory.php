<?php

namespace Webkul\Measurement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Measurement\Models\MeasurementFamily;

class MeasurementFamilyFactory extends Factory
{
    protected $model = MeasurementFamily::class;

    public function definition(): array
    {
        return [
            'code'          => $this->faker->unique()->word,
            'name'          => $this->faker->word,
            'standard_unit' => 'meter',
            'symbol'        => 'm',
            'labels'        => [
                'en_US' => $this->faker->word,
            ],
            'units' => [],
        ];
    }
}

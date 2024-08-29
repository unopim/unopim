<?php

namespace Webkul\Attribute\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\AttributeGroup;

class AttributeGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AttributeGroup::class;

    /**
     * @var array
     */
    protected $states = [
        'required',
        'unique',
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->regexify('/^[a-zA-Z]+[a-zA-Z0-9_]+$/'),
        ];
    }

    public function required(): AttributeGroupFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_required' => true,
            ];
        });
    }

    public function unique(): AttributeGroupFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_unique' => true,
            ];
        });
    }
}

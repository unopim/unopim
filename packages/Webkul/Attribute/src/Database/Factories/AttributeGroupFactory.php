<?php

namespace Webkul\Attribute\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\AttributeGroup;

/**
 * @extends Factory<AttributeGroup>
 */
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
            'code' => fake()->regexify('/^[a-zA-Z]+\w+$/'),
        ];
    }

    public function required(): AttributeGroupFactory
    {
        return $this->state(fn (array $attributes): array => [
            'is_required' => true,
        ]);
    }

    public function unique(): AttributeGroupFactory
    {
        return $this->state(fn (array $attributes): array => [
            'is_unique' => true,
        ]);
    }
}

<?php

namespace Webkul\Attribute\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeColumn;
use Webkul\Attribute\Models\AttributeOption;

class AttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attribute::class;

    /**
     * @var array
     */
    protected $states = [
        'validation_numeric',
        'validation_email',
        'validation_decimal',
        'validation_url',
        'required',
        'unique',
        'filterable',
        'configurable',
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $types = [
            'text',
            'textarea',
            'price',
            'boolean',
            'select',
            'multiselect',
            'datetime',
            'date',
            'image',
            'file',
            'checkbox',
        ];

        return [
            'code'              => $this->faker->regexify('/^[a-zA-Z]+[a-zA-Z0-9_]+$/'),
            'type'              => array_rand($types),
            'validation'        => '',
            'position'          => $this->faker->randomDigit,
            'is_required'       => false,
            'is_unique'         => false,
            'value_per_locale'  => false,
            'value_per_channel' => false,
            'swatch_type'       => null,
        ];
    }

    public function validation_numeric(): AttributeFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'validation' => 'numeric',
            ];
        });
    }

    public function validation_email(): AttributeFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'validation' => 'email',
            ];
        });
    }

    public function validation_decimal(): AttributeFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'validation' => 'decimal',
            ];
        });
    }

    public function validation_url(): AttributeFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'validation' => 'url',
            ];
        });
    }

    public function required(): AttributeFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_required' => true,
            ];
        });
    }

    public function unique(): AttributeFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_unique' => true,
            ];
        });
    }

    /**
     * Configure the model
     */
    public function configure()
    {
        return $this->afterCreating(function (Attribute $attribute) {
            if (in_array($attribute->type, ['select', 'multiselect', 'checkbox'])) {
                AttributeOption::factory()->count(3)->create(['attribute_id' => $attribute->id]);
            }
            if ($attribute->type == 'table') {
                AttributeColumn::factory()->count(3)->create(['attribute_id' => $attribute->id]);
            }
        });
    }
}

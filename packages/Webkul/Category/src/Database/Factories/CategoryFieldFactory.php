<?php

namespace Webkul\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Category\Models\CategoryField;
use Webkul\Category\Models\CategoryFieldOption;

class CategoryFieldFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CategoryField::class;

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
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $types = [
            'text',
            'textarea',
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
            'name'             => $this->faker->word,
            'code'             => $this->faker->regexify('/^[a-zA-Z]+[a-zA-Z0-9_]+$/'),
            'type'             => array_rand($types),
            'validation'       => '',
            'position'         => $this->faker->randomDigit,
            'is_required'      => false,
            'is_unique'        => false,
            'value_per_locale' => false,
            'section'          => $this->faker->randomElement(['left', 'right']),
            'status'           => $this->faker->boolean ? 1 : 0,
        ];
    }

    public function validation_numeric(): CategoryFieldFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'validation' => 'numeric',
            ];
        });
    }

    public function validation_email(): CategoryFieldFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'validation' => 'email',
            ];
        });
    }

    public function validation_decimal(): CategoryFieldFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'validation' => 'decimal',
            ];
        });
    }

    public function validation_url(): CategoryFieldFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'validation' => 'url',
            ];
        });
    }

    public function required(): CategoryFieldFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_required' => true,
            ];
        });
    }

    public function unique(): CategoryFieldFactory
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
        return $this->afterCreating(function (CategoryField $field) {
            if (in_array($field->type, ['select', 'multiselect', 'checkbox'])) {
                CategoryFieldOption::factory()->count(2)->create(['category_field_id' => $field->id]);
            }
        });
    }
}

<?php

namespace Webkul\Category\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Category\Models\CategoryField;

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
            'name'                => $this->faker->word,
            'code'                => $this->faker->regexify('/^[a-zA-Z]+[a-zA-Z0-9_]+$/'),
            'type'                => array_rand($types),
            'validation'          => '',
            'position'            => $this->faker->randomDigit,
            'is_required'         => false,
            'is_unique'           => false,
            'value_per_locale'    => false,
            'value_per_channel'   => false,
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
}

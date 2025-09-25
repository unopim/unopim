<?php

namespace Webkul\Attribute\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\AttributeColumn;

class AttributeColumnFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AttributeColumn::class;

    protected $state;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->word,
        ];
    }

    /**
     * Type text.
     */
    public function text(): AttributeColumnFactory
    {
        return $this->state(function () {
            return [
                'type' => 'text',
            ];
        });
    }

    /**
     * Type boolean.
     */
    public function boolean(): AttributeColumnFactory
    {
        return $this->state(function () {
            return [
                'type' => 'boolean',
            ];
        });
    }

    /**
     * Type date.
     */
    public function date(): AttributeColumnFactory
    {
        return $this->state(function () {
            return [
                'type' => 'date',
            ];
        });
    }

    /**
     * Type image.
     */
    public function image(): AttributeColumnFactory
    {
        return $this->state(function () {
            return [
                'type' => 'image',
            ];
        });
    }

    /**
     * Type select.
     */
    public function select(): AttributeColumnFactory
    {
        return $this->state(function () {
            return [
                'type' => 'select',
            ];
        });
    }

    /**
     * Type multiselect.
     */
    public function multiselect(): AttributeColumnFactory
    {
        return $this->state(function () {
            return [
                'type' => 'multiselect',
            ];
        });
    }
}

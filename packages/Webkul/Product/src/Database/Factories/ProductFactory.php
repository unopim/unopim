<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * States.
     *
     * @var string[]
     */
    protected $states = [
        'simple',
        'configurable',
    ];

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'sku'                 => $this->faker->uuid,
            'status'              => 0,
            'attribute_family_id' => AttributeFamily::find(1)?->id ?? AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create()->id,
        ];
    }

    public function withInitialValues(): ProductFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'values' => [
                    'common' => [
                        'sku'    => $attributes['sku'],
                        'status' => 'false',
                    ],
                ],
            ];
        });
    }

    /**
     * Simple state.
     */
    public function simple(): ProductFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'simple',
            ];
        });
    }

    /**
     * Configurable state.
     */
    public function configurable(): ProductFactory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'configurable',
            ];
        });
    }

    /**
     * For configurable product with variant option attributes
     */
    public function withConfigurableAttributes(): ProductFactory
    {
        return $this->afterCreating(function (Product $product) {
            if ($product->type === 'configurable') {
                $product->super_attributes()->attach($product->attribute_family->getConfigurableAttributes()->first());
            }
        });
    }

    /**
     * Add variant product to configurable product
     */
    public function withVariantProduct(): ProductFactory
    {
        return $this->afterCreating(function (Product $product) {
            if ($product->type === 'configurable') {
                $product->super_attributes()->attach($product->attribute_family->getConfigurableAttributes()->first());

                $attribute = $product->super_attributes->first();

                $firstOption = $attribute->options->first()->code;

                Product::factory()->create([
                    'parent_id' => $product->id,
                    'values'    => [
                        'common' => [
                            $attribute->code => $firstOption,
                        ],
                    ],
                ]);
            }
        });
    }
}

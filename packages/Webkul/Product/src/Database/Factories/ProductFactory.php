<?php

namespace Webkul\Product\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;

/**
 * @extends Factory<Product>
 */
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
            'sku'                 => fake()->unique()->regexify('[A-Z]{3}[0-9]{4}'),
            'type'                => 'simple',
            'status'              => 1,
            'attribute_family_id' => AttributeFamily::find(1)?->id ?? AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create()->id,
        ];
    }

    public function withInitialValues(): ProductFactory
    {
        return $this->state(fn (array $attributes): array => [
            'values' => [
                'common' => [
                    'sku'    => $attributes['sku'],
                ],
            ],
        ]);
    }

    /**
     * Simple state.
     */
    public function simple(): ProductFactory
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'simple',
        ]);
    }

    /**
     * Configurable state.
     */
    public function configurable(): ProductFactory
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'configurable',
        ]);
    }

    /**
     * For configurable product with variant option attributes
     */
    public function withConfigurableAttributes(): ProductFactory
    {
        return $this->afterCreating(function (Product $product): void {
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
        return $this->afterCreating(function (Product $product): void {
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

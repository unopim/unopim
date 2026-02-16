<?php

namespace Webkul\Shopify\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Shopify\Models\ShopifyMetaFieldsConfig;

class ShopifyMetaFieldFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ShopifyMetaFieldsConfig::class;

    /**
     * Define the model's default state.
     * Fake credentials are used for the testing purposes.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code'           => 'testing',
            'attribute'      => 'testing',
            'name_space'     => 'testingnamespace',
            'name_space_key' => 'testingnamespacekey',
            'type'           => 'testingtype',
            'ownerType'      => 'testingowner',
        ];
    }
}

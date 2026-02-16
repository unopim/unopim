<?php

namespace Webkul\Shopify\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Shopify\Models\ShopifyCredentialsConfig;

class ShopifyCredentialFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ShopifyCredentialsConfig::class;

    /**
     * Define the model's default state.
     * Fake credentials are used for the testing purposes.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'shopUrl'      => 'https://demotest.myshopify.com',
            'apiVersion'   => '2025-01',
            'accessToken'  => '$2y$10$92IXUNpkjO0rOQ5byMi',
        ];
    }
}

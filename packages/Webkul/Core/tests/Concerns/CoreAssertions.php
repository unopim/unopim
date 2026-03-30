<?php

namespace Webkul\Core\Tests\Concerns;

trait CoreAssertions
{
    /**
     * Assert model wise.
     */
    public function assertModelWise(array $modelWiseAssertions): void
    {
        foreach ($modelWiseAssertions as $modelClassName => $modelAssertions) {
            foreach ($modelAssertions as $assertion) {
                $this->assertDatabaseHas(app($modelClassName)->getTable(), $assertion);
            }
        }
    }

    /**
     * Assert that a product exists in the database by SKU.
     */
    public function assertProductExists(string $sku, array $additional = []): void
    {
        $this->assertDatabaseHas('wk_products', array_merge(['sku' => $sku], $additional));
    }

    /**
     * Assert that a product does not exist in the database by SKU.
     */
    public function assertProductMissing(string $sku): void
    {
        $this->assertDatabaseMissing('wk_products', ['sku' => $sku]);
    }

    /**
     * Assert that an attribute exists by code.
     */
    public function assertAttributeExists(string $code, array $additional = []): void
    {
        $this->assertDatabaseHas('wk_attributes', array_merge(['code' => $code], $additional));
    }

    /**
     * Assert that a category exists by code.
     */
    public function assertCategoryExists(string $code, array $additional = []): void
    {
        $this->assertDatabaseHas('wk_categories', array_merge(['code' => $code], $additional));
    }

    /**
     * Assert that an attribute family exists by code.
     */
    public function assertAttributeFamilyExists(string $code, array $additional = []): void
    {
        $this->assertDatabaseHas('wk_attribute_families', array_merge(['code' => $code], $additional));
    }

    /**
     * Assert that a channel exists by code.
     */
    public function assertChannelExists(string $code, array $additional = []): void
    {
        $this->assertDatabaseHas('wk_channels', array_merge(['code' => $code], $additional));
    }

    /**
     * Assert that a locale exists by code with given status.
     */
    public function assertLocaleExists(string $code, bool $active = true): void
    {
        $this->assertDatabaseHas('wk_locales', [
            'code'   => $code,
            'status' => $active,
        ]);
    }

    /**
     * Assert JSON response has expected structure with message and redirect.
     */
    public function assertJsonResponseStructure($response): void
    {
        $response->assertJsonStructure(['message']);
    }

    /**
     * Assert a successful store/update JSON response.
     */
    public function assertSuccessJsonResponse($response): void
    {
        $response->assertOk();
        $response->assertJsonStructure(['message', 'redirect_url']);
    }

    /**
     * Assert a core config value exists in the database.
     */
    public function assertCoreConfigValue(string $code, string $value, ?string $channelCode = null, ?string $localeCode = null): void
    {
        $this->assertDatabaseHas('wk_core_config', array_filter([
            'code'         => $code,
            'value'        => $value,
            'channel_code' => $channelCode,
            'locale_code'  => $localeCode,
        ]));
    }
}

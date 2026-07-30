<?php

use Illuminate\Support\Facades\DB;
use Webkul\Product\Models\Product;

it('separates the more-actions dropdown from the side rail collapse toggle', function () {
    $this->loginAsAdmin();

    DB::table('core_config')->updateOrInsert(
        ['code' => 'general.magic_ai.translation.enabled', 'channel_code' => null, 'locale_code' => null],
        ['value' => '1', 'created_at' => now(), 'updated_at' => now()]
    );

    $product = Product::factory()->create(['type' => 'simple']);

    $content = $this->get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('relative inline-block text-left ltr:mr-11 rtl:ml-11');
});

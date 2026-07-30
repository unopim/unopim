<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Jobs\System\BulkProductUpdate;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Models\WebhookLog;

it('records the acting admin on a bulk-edit webhook log even with no request-scoped auth', function () {
    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    Webhook::create([
        'name'      => 'Test Hook',
        'url'       => 'https://example.test/hook',
        'is_active' => true,
        'events'    => ['product.updated'],
    ]);

    $family = AttributeFamily::find(1)
        ?? AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create();

    $product = Product::factory()->withInitialValues()->create(['attribute_family_id' => $family->id]);

    $attribute = Attribute::factory()->create(['value_per_locale' => false, 'value_per_channel' => false, 'type' => 'text']);
    $family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    Auth::logout();

    (new BulkProductUpdate([$product->id => [$attribute->code => 'bulk-changed-value']], $admin->id))->handle();

    $log = WebhookLog::latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log->user)->toBe($admin->name);
});

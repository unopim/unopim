<?php

use Illuminate\Support\Facades\Queue;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Webhook\Jobs\SendBulkEditProductWebhook;
use Webkul\Webhook\Jobs\SendProductWebhook;
use Webkul\Webhook\Models\Webhook;

it('sends only the batched webhook, not a per-row one, for a bulk-edited product', function () {
    $this->loginAsAdmin();

    Queue::fake([SendProductWebhook::class, SendBulkEditProductWebhook::class]);

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

    $this->postJson(route('admin.catalog.products.bulk-edit.save'), [
        'data' => [$product->id => [$attribute->code => 'bulk-changed-value']],
    ])->assertOk();

    Queue::assertNotPushed(SendProductWebhook::class);
    Queue::assertPushed(SendBulkEditProductWebhook::class, 1);
});

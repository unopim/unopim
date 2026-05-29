<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\Product\Models\Product;
use Webkul\Webhook\Jobs\SendProductWebhook;
use Webkul\Webhook\Services\WebhookService;

beforeEach(function () {
    DB::table('webhook_settings')->updateOrInsert(
        ['field' => 'webhook_url'],
        ['value' => 'https://example.test/hook', 'updated_at' => now(), 'created_at' => now()]
    );

    DB::table('webhook_settings')->updateOrInsert(
        ['field' => 'webhook_active'],
        ['value' => '1', 'updated_at' => now(), 'created_at' => now()]
    );
});

afterEach(function () {
    DB::table('webhook_settings')->whereIn('field', ['webhook_url', 'webhook_active'])->delete();
});

it('dispatches SendProductWebhook with the created event and the new product sku in changes.added', function () {
    $this->loginAsAdmin();

    Bus::fake([SendProductWebhook::class]);

    $data = Product::factory()->definition();
    $data['type'] = 'simple';

    $this->post(route('admin.catalog.products.store'), $data)
        ->assertOk()
        ->assertSessionHas('success', trans('admin::app.catalog.products.create-success'));

    Bus::assertDispatched(SendProductWebhook::class, function (SendProductWebhook $job) use ($data) {
        $reflection = new ReflectionClass($job);

        $eventTypeProp = $reflection->getProperty('eventType');

        $productIdProp = $reflection->getProperty('productId');

        $changesProp = $reflection->getProperty('changes');

        $createdProduct = Product::where('sku', $data['sku'])->first();
        $changes = $changesProp->getValue($job);

        return $eventTypeProp->getValue($job) === 'created'
            && $productIdProp->getValue($job) === $createdProduct->id
            && ($changes['added']['sku'] ?? null) === $createdProduct->sku
            && ($changes['added']['type'] ?? null) === 'simple';
    });
});

it('does not dispatch SendProductWebhook when the webhook is inactive', function () {
    $this->loginAsAdmin();

    DB::table('webhook_settings')
        ->where('field', 'webhook_active')
        ->update(['value' => '0']);

    Bus::fake([SendProductWebhook::class]);

    $data = Product::factory()->definition();
    $data['type'] = 'simple';

    $this->post(route('admin.catalog.products.store'), $data)
        ->assertOk();

    Bus::assertNotDispatched(SendProductWebhook::class);
});

it('passes the dispatching admin id into the SendProductWebhook job', function () {
    $admin = $this->loginAsAdmin();

    Bus::fake([SendProductWebhook::class]);

    $data = Product::factory()->definition();
    $data['type'] = 'simple';

    $this->post(route('admin.catalog.products.store'), $data)
        ->assertOk();

    Bus::assertDispatched(SendProductWebhook::class, function (SendProductWebhook $job) use ($admin) {
        $reflection = new ReflectionClass($job);

        $userIdProp = $reflection->getProperty('userId');

        return $userIdProp->getValue($job) === $admin->id;
    });
});

it('produces a product.created payload via the webhook service for a created product', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create();

    $service = app(WebhookService::class);

    Http::fake();

    $service->sendCreatedToWebhook($product);

    Http::assertSent(function (Request $request) use ($product) {
        $body = $request->data();

        return ($body['event'] ?? null) === 'product.created'
            && ($body['data'][0]['sku'] ?? null) === $product->sku;
    });
});

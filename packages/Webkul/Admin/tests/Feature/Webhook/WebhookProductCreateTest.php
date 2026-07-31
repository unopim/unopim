<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\Product\Models\Product;
use Webkul\Webhook\Jobs\SendProductWebhook;
use Webkul\Webhook\Services\WebhookService;

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);
    DB::table('webhooks')->delete();
    DB::table('webhooks')->insert([
        'name'       => 'Create Test',
        'url'        => 'https://1.1.1.1/hook',
        'is_active'  => 1,
        'events'     => json_encode(['product.created', 'product.updated']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
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
        $eventTypeProp->setAccessible(true);

        $productIdProp = $reflection->getProperty('productId');
        $productIdProp->setAccessible(true);

        $changesProp = $reflection->getProperty('changes');
        $changesProp->setAccessible(true);

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

    DB::table('webhooks')->update(['is_active' => 0]);

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
        $userIdProp->setAccessible(true);

        return $userIdProp->getValue($job) === $admin->id;
    });
});

it('produces a product.created payload via the webhook service for a created product', function () {
    $this->loginAsAdmin();

    $product = Product::factory()->create();

    $service = app(WebhookService::class);

    Http::fake();

    $service->sendCreatedToWebhook($product);

    Http::assertSent(function ($request) use ($product) {
        $body = $request->data();

        return ($body['event'] ?? null) === 'product.created'
            && ($body['data'][0]['sku'] ?? null) === $product->sku;
    });
});

<?php

use Illuminate\Support\Facades\Http;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Product\Models\Product;
use Webkul\Webhook\Helpers\ProductComparer;
use Webkul\Webhook\Models\Webhook;
use Webkul\Webhook\Models\WebhookLog;
use Webkul\Webhook\Services\WebhookService;

beforeEach(function () {
    $this->loginAsAdmin();
});

function webhookMeasurementSetup(): array
{
    $suffix = uniqid();

    $family = MeasurementFamily::factory()->create([
        'code'          => 'length_'.$suffix,
        'standard_unit' => 'meter',
        'symbol'        => 'm',
        'units'         => [
            ['code' => 'meter', 'symbol' => 'm', 'labels' => ['en_US' => 'Meter']],
            [
                'code'                  => 'cm',
                'symbol'                => 'cm',
                'labels'                => ['en_US' => 'Centimeter'],
                'convert_from_standard' => [['operator' => 'mul', 'value' => '100']],
            ],
        ],
    ]);

    $attribute = Attribute::factory()->create([
        'code' => 'width_'.$suffix,
        'type' => 'measurement',
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);

    return [$attribute, $family];
}

function activeMeasurementWebhook(): Webhook
{
    return Webhook::create([
        'name'      => 'measurement-'.uniqid(),
        'url'       => 'https://8.8.8.8/measurement-hook',
        'is_active' => true,
        'events'    => ['product.created', 'product.updated'],
    ]);
}

it('carries the stored measurement structure in the product audit diff that becomes the webhook changes payload', function () {
    [$attribute, $family] = webhookMeasurementSetup();

    $structure = app(MeasurementHelper::class)->buildValueStructure('10', 'meter', $family->code, $family);

    $sku = 'meas-hook-'.uniqid();

    $product = Product::factory()->create([
        'sku'    => $sku,
        'type'   => 'simple',
        'status' => 1,
        'values' => [
            'common' => [
                'sku'            => $sku,
                $attribute->code => $structure,
            ],
        ],
    ]);

    $audit = $product->audits()->where('event', 'created')->latest()->first();

    expect($audit)->not->toBeNull();

    $changes = ProductComparer::compare($audit->old_values ?? [], $audit->new_values ?? []);

    $stored = $changes['added']['common'][$attribute->code] ?? null;

    expect($stored)->toBeArray()
        ->and($stored['unit'] ?? null)->toBe('meter')
        ->and($stored['family'] ?? null)->toBe($family->code)
        ->and((float) ($stored['amount'] ?? 0))->toBe(10.0)
        ->and((float) ($stored['base_data'] ?? 0))->toBe(10.0)
        ->and($stored['base_unit'] ?? null)->toBe('meter');
});

it('delivers the full measurement value structure inside the webhook request payload', function () {
    Http::fake();

    [$attribute, $family] = webhookMeasurementSetup();

    $structure = app(MeasurementHelper::class)->buildValueStructure('10', 'meter', $family->code, $family);

    $sku = 'meas-hook-'.uniqid();

    $product = Product::factory()->create([
        'sku'    => $sku,
        'type'   => 'simple',
        'status' => 1,
        'values' => [
            'common' => [
                'sku'            => $sku,
                $attribute->code => $structure,
            ],
        ],
    ]);

    $changes = ProductComparer::compare(
        ['values' => json_encode(['common' => []]), 'status' => 1],
        ['values' => json_encode(['common' => [$attribute->code => $structure]]), 'status' => 1],
    );

    activeMeasurementWebhook();

    app(WebhookService::class)->sendCreatedToWebhook($product->fresh(), $changes);

    Http::assertSent(function ($request) use ($attribute, $family, $sku): bool {
        $body = json_decode($request->body(), true);

        $stored = $body['data'][0]['changes']['added']['common'][$attribute->code] ?? null;

        return ($body['event'] ?? null) === 'product.created'
            && ($body['data'][0]['sku'] ?? null) === $sku
            && is_array($stored)
            && ($stored['unit'] ?? null) === 'meter'
            && ($stored['family'] ?? null) === $family->code
            && (float) ($stored['amount'] ?? 0) === 10.0
            && (float) ($stored['base_data'] ?? 0) === 10.0;
    });
});

it('persists the delivered measurement payload in the webhook log', function () {
    Http::fake();

    [$attribute, $family] = webhookMeasurementSetup();

    $structure = app(MeasurementHelper::class)->buildValueStructure('7', 'meter', $family->code, $family);

    $sku = 'meas-hook-'.uniqid();

    $product = Product::factory()->create([
        'sku'    => $sku,
        'type'   => 'simple',
        'status' => 1,
        'values' => [
            'common' => [
                'sku'            => $sku,
                $attribute->code => $structure,
            ],
        ],
    ]);

    $changes = ProductComparer::compare(
        ['values' => json_encode(['common' => []]), 'status' => 1],
        ['values' => json_encode(['common' => [$attribute->code => $structure]]), 'status' => 1],
    );

    $webhook = activeMeasurementWebhook();

    app(WebhookService::class)->sendDataToWebhook($product->fresh(), $changes);

    $log = WebhookLog::where('webhook_id', $webhook->id)->where('sku', $sku)->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->event)->toBe('product.updated')
        ->and((int) $log->status)->toBe(1);

    $stored = $log->extra['payload']['data'][0]['changes']['added']['common'][$attribute->code] ?? null;

    expect($stored)->toBeArray()
        ->and($stored['unit'] ?? null)->toBe('meter')
        ->and((float) ($stored['amount'] ?? 0))->toBe(7.0)
        ->and((float) ($stored['base_data'] ?? 0))->toBe(7.0);
});

it('reports a measurement unit and amount change with the recomputed base_data as a changed diff and delivers it', function () {
    Http::fake();

    [$attribute, $family] = webhookMeasurementSetup();

    $helper = app(MeasurementHelper::class);

    $old = $helper->buildValueStructure('10', 'meter', $family->code, $family);
    $new = $helper->buildValueStructure('500', 'cm', $family->code, $family);

    $changes = ProductComparer::compare(
        ['values' => json_encode(['common' => [$attribute->code => $old]]), 'status' => 1],
        ['values' => json_encode(['common' => [$attribute->code => $new]]), 'status' => 1],
    );

    $changed = $changes['changed']['common'][$attribute->code] ?? null;

    expect($changed)->toBeArray()
        ->and($changed['old']['unit'] ?? null)->toBe('meter')
        ->and($changed['new']['unit'] ?? null)->toBe('cm')
        ->and((float) ($changed['old']['base_data'] ?? 0))->toBe(10.0)
        ->and((float) ($changed['new']['base_data'] ?? 0))->toBe(5.0);

    $sku = 'meas-hook-'.uniqid();

    $product = Product::factory()->create([
        'sku'    => $sku,
        'type'   => 'simple',
        'status' => 1,
        'values' => [
            'common' => [
                'sku'            => $sku,
                $attribute->code => $new,
            ],
        ],
    ]);

    activeMeasurementWebhook();

    app(WebhookService::class)->sendDataToWebhook($product->fresh(), $changes);

    Http::assertSent(function ($request) use ($attribute): bool {
        $body = json_decode($request->body(), true);

        $c = $body['data'][0]['changes']['changed']['common'][$attribute->code] ?? null;

        return is_array($c)
            && ($c['new']['unit'] ?? null) === 'cm'
            && (float) ($c['new']['base_data'] ?? 0) === 5.0
            && (float) ($c['old']['base_data'] ?? 0) === 10.0;
    });
});

it('does not fan out a measurement payload to webhooks not subscribed to the fired event', function () {
    Http::fake();

    [$attribute, $family] = webhookMeasurementSetup();

    $structure = app(MeasurementHelper::class)->buildValueStructure('3', 'meter', $family->code, $family);

    $sku = 'meas-hook-'.uniqid();

    $product = Product::factory()->create([
        'sku'    => $sku,
        'type'   => 'simple',
        'status' => 1,
        'values' => [
            'common' => [
                'sku'            => $sku,
                $attribute->code => $structure,
            ],
        ],
    ]);

    Webhook::create([
        'name'      => 'created-only-'.uniqid(),
        'url'       => 'https://8.8.8.8/measurement-hook',
        'is_active' => true,
        'events'    => ['product.created'],
    ]);

    $changes = ProductComparer::compare(
        ['values' => json_encode(['common' => []]), 'status' => 1],
        ['values' => json_encode(['common' => [$attribute->code => $structure]]), 'status' => 1],
    );

    app(WebhookService::class)->sendDataToWebhook($product->fresh(), $changes);

    Http::assertNothingSent();
});

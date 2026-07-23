<?php

use Illuminate\Support\Facades\Bus;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\ImportProducts;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Jobs\Import\ImportTrackBatch;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->tempImportFiles = [];

    AttributeFamily::query()->first()
        ?? AttributeFamily::factory()->withMinimalAttributesForProductTypes()->create([
            'code' => 'default',
        ]);
});

afterEach(function () {
    foreach ($this->tempImportFiles as $file) {
        if (is_string($file) && file_exists($file)) {
            @unlink($file);
        }
    }
});

it('denies AI import when the user lacks import execute permission', function () {
    $admin = $this->loginWithPermissions('custom', ['catalog', 'catalog.products', 'catalog.products.create']);
    $context = buildAiImportChatContext($admin);

    $result = decodeToolResult(app(ImportProducts::class)->register($context)->handle(new Request([])));

    expect($result['error'])->toContain('data_transfer.imports.execute');
});

it('denies AI import when the user has import execute permission but no product create or edit access', function () {
    $admin = $this->loginWithPermissions('custom', ['data_transfer', 'data_transfer.imports.execute']);
    $context = buildAiImportChatContext($admin);

    $result = decodeToolResult(app(ImportProducts::class)->register($context)->handle(new Request([])));

    expect($result['error'])->toContain('catalog.products.create or catalog.products.edit');
});

it('queues eligible rows through the core import pipeline when the user has execute and create permission', function () {
    Bus::fake();

    $admin = $this->loginWithPermissions('custom', [
        'data_transfer',
        'data_transfer.imports.execute',
        'catalog',
        'catalog.products',
        'catalog.products.create',
    ]);

    $filePath = createTempImportFile($this, [
        ['sku', 'name'],
        ['AI-NEW-100', 'AI Imported Product'],
    ]);

    $context = buildAiImportChatContext($admin, $filePath);
    $result = decodeToolResult(app(ImportProducts::class)->register($context)->handle(new Request([])));

    expect(data_get($result, 'result.queued_rows'))->toBe(1)
        ->and(data_get($result, 'result.skipped'))->toBe(0)
        ->and(data_get($result, 'result.tracker_id'))->not->toBeNull();

    Bus::assertDispatched(ImportTrackBatch::class);
});

it('skips existing products a user cannot edit and queues only the eligible new rows', function () {
    Bus::fake();

    Product::factory()->simple()->withInitialValues()->create([
        'sku'    => 'AI-EXISTING-100',
        'status' => 0,
    ]);

    $admin = $this->loginWithPermissions('custom', [
        'data_transfer',
        'data_transfer.imports.execute',
        'catalog',
        'catalog.products',
        'catalog.products.create',
    ]);

    $filePath = createTempImportFile($this, [
        ['sku', 'status'],
        ['AI-EXISTING-100', 'active'],
        ['AI-NEW-101', 'active'],
    ]);

    $context = buildAiImportChatContext($admin, $filePath);
    $result = decodeToolResult(app(ImportProducts::class)->register($context)->handle(new Request([])));

    expect(data_get($result, 'result.queued_rows'))->toBe(1)
        ->and(data_get($result, 'result.skipped'))->toBe(1)
        ->and(data_get($result, 'result.errors.0'))->toContain('catalog.products.edit');

    Bus::assertDispatched(ImportTrackBatch::class);

    // The existing product is filtered out, not modified, and the eligible
    // new row is queued rather than created inline.
    expect((int) Product::query()->where('sku', 'AI-EXISTING-100')->value('status'))->toBe(0)
        ->and(Product::query()->where('sku', 'AI-NEW-101')->exists())->toBeFalse();
});

/**
 * Build a chat context for direct AI import tool tests.
 */
function buildAiImportChatContext($admin, ?string $filePath = null): ChatContext
{
    return new ChatContext(
        message: 'Import products from file',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform([
            'provider' => 'openai',
            'models'   => 'gpt-4o',
        ]),
        model: 'gpt-4o',
        uploadedImagePaths: [],
        uploadedFilePaths: $filePath ? [$filePath] : [],
        currentPage: null,
        user: $admin,
    );
}

/**
 * Create a temporary CSV import file for AI tool tests.
 *
 * @param  array<int, array<int, string>>  $rows
 */
function createTempImportFile($testCase, array $rows): string
{
    $filePath = tempnam(sys_get_temp_dir(), 'ai-import-');

    if ($filePath === false) {
        throw new RuntimeException('Unable to create temporary import file.');
    }

    $csvPath = $filePath.'.csv';

    rename($filePath, $csvPath);

    $handle = fopen($csvPath, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    $testCase->tempImportFiles[] = $csvPath;

    return $csvPath;
}

/**
 * Decode a Prism tool JSON string response into an array.
 */
function decodeToolResult(string $result): array
{
    return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
}

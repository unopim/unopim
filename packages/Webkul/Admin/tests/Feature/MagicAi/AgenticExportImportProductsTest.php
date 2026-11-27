<?php

use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\ExportProducts;
use Webkul\AiAgent\Chat\Tools\ImportProducts;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\Product\Models\Product;

function agenticTransferContext($admin, array $files = []): ChatContext
{
    return new ChatContext(
        message: 'transfer products',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform(['provider' => 'openai', 'models' => 'gpt-4o']),
        model: 'gpt-4o',
        uploadedImagePaths: [],
        uploadedFilePaths: $files,
        currentPage: null,
        user: $admin,
    );
}

it('exports products to a file downloadable from the chat and the job tracker', function () {
    Storage::fake('private');

    Product::factory()->simple()->withInitialValues()->create(['sku' => 'AGENTIC-EXPORT-1']);

    $admin = $this->loginWithPermissions('custom', ['data_transfer', 'data_transfer.export', 'data_transfer.export.execute', 'data_transfer.job_tracker']);

    $result = json_decode(app(ExportProducts::class)->register(agenticTransferContext($admin))
        ->handle(new Request(['skus' => 'AGENTIC-EXPORT-1', 'status' => 'active'])), true);

    $trackerId = data_get($result, 'result.tracker_id');

    expect($result)->not->toHaveKey('error')
        ->and(data_get($result, 'result.exported'))->toBe(1)
        ->and(JobTrack::find($trackerId)->state)->toBe('completed');

    foreach ([$result['download_url'], route('admin.settings.data_transfer.tracker.download', $trackerId)] as $url) {
        $response = $this->get($url);

        $response->assertOk();

        expect($response->streamedContent())->toContain('AGENTIC-EXPORT-1');
    }
});

it('imports products from an uploaded csv into the catalog', function (string $csv) {
    $admin = $this->loginWithPermissions('custom', [
        'data_transfer',
        'data_transfer.imports.execute',
        'catalog',
        'catalog.products',
        'catalog.products.create',
        'catalog.products.edit',
    ]);

    $path = tempnam(sys_get_temp_dir(), 'agentic-import-').'.csv';
    file_put_contents($path, $csv);

    $result = json_decode(app(ImportProducts::class)->register(agenticTransferContext($admin, [$path]))
        ->handle(new Request([])), true);

    @unlink($path);

    expect($result)->not->toHaveKey('error')
        ->and(data_get($result, 'result.queued_rows'))->toBe(1)
        ->and(JobTrack::find(data_get($result, 'result.tracker_id'))->state)->toBe('completed');

    $product = Product::where('sku', 'AGENTIC-IMPORT-1')->first();

    expect($product)->not->toBeNull()
        ->and(data_get($product->values, 'channel_locale_specific.default.en_US.price.USD'))->toBe('12.5');
})->with([
    'plain columns'       => ["sku,name,description,short_description,price,weight,status\nAGENTIC-IMPORT-1,Agentic,Desc,Short,12.5,1,active\n"],
    'core price columns'  => ["sku;type;channel;locale;status;name;description;short_description;price (USD);price (EUR);weight\nAGENTIC-IMPORT-1;simple;default;en_US;true;Agentic;Desc;Short;12.5;11;1\n"],
]);

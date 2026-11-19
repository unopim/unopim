<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\ImportProducts;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;

it('links the queued import to its job tracker progress page, not the import profile', function () {
    Bus::fake();

    $admin = Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    $csv = storage_path('framework/testing/ai-import-'.uniqid().'.csv');
    File::ensureDirectoryExists(dirname($csv));
    File::put($csv, "sku,name\nTRACKER-URL-1,Cable Tray\n");

    $context = new ChatContext(
        message: 'Import this file',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: new MagicAIPlatform,
        uploadedFilePaths: [$csv],
        user: $admin,
    );

    $response = json_decode(app(ImportProducts::class)->register($context)->handle(new Request([])), true);

    File::delete($csv);

    expect($response['result']['tracker_url'])
        ->toBe(route('admin.settings.data_transfer.tracker.view', $response['result']['tracker_id']));
});

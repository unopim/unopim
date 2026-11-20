<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\AiAgent\Chat\Tools\ExportProducts;

/**
 * Store an AI export for the given admin user and return its file name.
 */
function storeAiExportFor(int $userId): string
{
    $file = Str::uuid().'.csv';

    Storage::disk('private')->put(ExportProducts::DIRECTORY.'/'.$userId.'/'.$file, "sku\nSKU-1\n");

    return $file;
}

beforeEach(function (): void {
    Storage::fake('private');
});

it('lets the user who ran the ai export download it', function (): void {
    $admin = $this->loginWithPermissions(permissions: ['dashboard', 'data_transfer', 'data_transfer.export', 'data_transfer.export.execute']);

    $file = storeAiExportFor($admin->id);

    $response = $this->get(route('ai-agent.exports.download', ['file' => $file]))->assertOk();

    expect($response->streamedContent())->toContain('SKU-1');
});

it('denies the ai export download without the export execute permission', function (): void {
    $admin = $this->loginWithPermissions(permissions: ['dashboard', 'data_transfer', 'data_transfer.export']);

    $file = storeAiExportFor($admin->id);

    $this->get(route('ai-agent.exports.download', ['file' => $file]))->assertForbidden();
});

it('does not serve another user\'s ai export', function (): void {
    $file = storeAiExportFor(999999);

    $this->loginWithPermissions(permissions: ['dashboard', 'data_transfer', 'data_transfer.export', 'data_transfer.export.execute']);

    $this->get(route('ai-agent.exports.download', ['file' => $file]))->assertNotFound();
});

it('redirects a guest away from the ai export download', function (): void {
    $file = storeAiExportFor(1);

    $this->get(route('ai-agent.exports.download', ['file' => $file]))->assertRedirect(route('admin.session.create'));
});

it('rejects a file name that is not a generated export', function (): void {
    $this->loginWithPermissions('all');

    $this->get(url(config('app.admin_url').'/ai-agent/exports/..%2F..%2F.env'))->assertNotFound();
});

<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Jobs\TenantAwareJob;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

// --- Story 4.2: DataTransfer jobs use TenantAwareJob ---

it('ImportBatch has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Import\ImportBatch::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('ImportTrackBatch has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Import\ImportTrackBatch::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('IndexBatch has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Import\IndexBatch::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('Indexing has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Import\Indexing::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('LinkBatch has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Import\LinkBatch::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('Linking has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Import\Linking::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('JobTrackBatch has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Import\JobTrackBatch::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('Import Completed has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Import\Completed::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('ExportBatch has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Export\ExportBatch::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('ExportTrackBatch has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Export\ExportTrackBatch::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('Export Completed has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Export\Completed::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('UploadFile has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\Export\UploadFile::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('BulkProductUpdate has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\DataTransfer\Jobs\System\BulkProductUpdate::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

// --- Story 4.3: MagicAI jobs use TenantAwareJob ---

it('SaveTranslatedDataJob has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\MagicAI\Jobs\SaveTranslatedDataJob::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

it('SaveTranslatedAllAttributesJob has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(\Webkul\MagicAI\Jobs\SaveTranslatedAllAttributesJob::class);
    expect($traits)->toHaveKey(TenantAwareJob::class);
});

// --- Story 4.6/4.7: Import/Export tenant isolation via Eloquent scope ---

it('Product model has TenantScope global scope for query-level filtering (FR37)', function () {
    $product = new \Webkul\Product\Models\Product;
    $scopes = $product->getGlobalScopes();

    expect($scopes)->toHaveKey(\Webkul\Tenant\Models\Scopes\TenantScope::class);
});

it('Product model auto-sets tenant_id on creation (FR34)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    // Create a product directly (simulating what import does)
    $product = \Webkul\Product\Models\Product::create([
        'sku'  => 'AUTO-TENANT-SKU-'.uniqid(),
        'type' => 'simple',
    ]);

    expect($product->tenant_id)->toBe($tenant->id);

    core()->setCurrentTenantId(null);
});

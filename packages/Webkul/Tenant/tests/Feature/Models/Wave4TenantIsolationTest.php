<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\DataTransfer\Models\JobTrackBatch;

/*
|--------------------------------------------------------------------------
| Wave 4 Tenant Isolation Tests
|--------------------------------------------------------------------------
|
| Proves that Wave 4 dependent tables have tenant_id columns
| and that JobTrackBatch model is properly tenant-scoped.
|
*/

// --- Story 6.3: Wave 4 migration adds tenant_id to dependent tables ---

it('has tenant_id on job_track_batches table', function () {
    expect(Schema::hasColumn('job_track_batches', 'tenant_id'))->toBeTrue();
});

it('has tenant_id on audits table', function () {
    if (! Schema::hasTable('audits')) {
        $this->markTestSkipped('audits table not present (owen-it/laravel-auditing)');
    }

    expect(Schema::hasColumn('audits', 'tenant_id'))->toBeTrue();
});

it('has tenant_id on attribute_translations table', function () {
    if (! Schema::hasTable('attribute_translations')) {
        $this->markTestSkipped('attribute_translations table not present');
    }

    expect(Schema::hasColumn('attribute_translations', 'tenant_id'))->toBeTrue();
});

it('has tenant_id on channel_translations table', function () {
    if (! Schema::hasTable('channel_translations')) {
        $this->markTestSkipped('channel_translations table not present');
    }

    expect(Schema::hasColumn('channel_translations', 'tenant_id'))->toBeTrue();
});

it('has tenant_id on product_relations table', function () {
    if (! Schema::hasTable('product_relations')) {
        $this->markTestSkipped('product_relations table not present');
    }

    expect(Schema::hasColumn('product_relations', 'tenant_id'))->toBeTrue();
});

it('has tenant_id on product_super_attributes table', function () {
    if (! Schema::hasTable('product_super_attributes')) {
        $this->markTestSkipped('product_super_attributes table not present');
    }

    expect(Schema::hasColumn('product_super_attributes', 'tenant_id'))->toBeTrue();
});

// --- Story 6.4: JobTrackBatch uses BelongsToTenant ---

it('isolates JobTrackBatch records between tenants', function () {
    // Create a job_track record for Tenant A
    $jobTrackId = DB::table('job_track')->insertGetId([
        'state'               => 'pending',
        'type'                => 'import',
        'action'              => 'import',
        'validation_strategy' => 'skip-errors',
        'field_separator'     => ',',
        'file_path'           => 'tmp/test-a.csv',
        'meta'                => json_encode([]),
        'job_instances_id'    => DB::table('job_instances')->insertGetId([
            'code'                => 'test-import-a',
            'entity_type'         => 'products',
            'type'                => 'import',
            'action'              => 'import',
            'validation_strategy' => 'skip-errors',
            'field_separator'     => ',',
            'file_path'           => 'tmp/test-a.csv',
            'tenant_id'           => $this->tenantA->id,
        ]),
        'tenant_id'  => $this->tenantA->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Create a batch for Tenant A
    DB::table('job_track_batches')->insert([
        'state'        => 'pending',
        'data'         => json_encode(['test' => true]),
        'job_track_id' => $jobTrackId,
        'tenant_id'    => $this->tenantA->id,
    ]);

    // Tenant A should see the batch
    $this->actingAsTenant($this->tenantA);
    expect(JobTrackBatch::count())->toBeGreaterThanOrEqual(1);

    // Tenant B should NOT see it
    $this->actingAsTenant($this->tenantB);
    expect(JobTrackBatch::count())->toBe(0);
});

it('auto-sets tenant_id on JobTrackBatch creation from context', function () {
    $jobTrackId = DB::table('job_track')->insertGetId([
        'state'               => 'pending',
        'type'                => 'import',
        'action'              => 'import',
        'validation_strategy' => 'skip-errors',
        'field_separator'     => ',',
        'file_path'           => 'tmp/test-auto.csv',
        'meta'                => json_encode([]),
        'job_instances_id'    => DB::table('job_instances')->insertGetId([
            'code'                => 'test-import-auto',
            'entity_type'         => 'products',
            'type'                => 'import',
            'action'              => 'import',
            'validation_strategy' => 'skip-errors',
            'field_separator'     => ',',
            'file_path'           => 'tmp/test-auto.csv',
            'tenant_id'           => $this->tenantA->id,
        ]),
        'tenant_id'  => $this->tenantA->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAsTenant($this->tenantA);

    $batch = JobTrackBatch::create([
        'state'        => 'pending',
        'data'         => ['auto' => true],
        'job_track_id' => $jobTrackId,
    ]);

    expect($batch->tenant_id)->toBe($this->tenantA->id);
});

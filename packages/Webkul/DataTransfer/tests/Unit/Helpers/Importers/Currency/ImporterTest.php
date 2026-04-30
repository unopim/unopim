<?php

namespace Webkul\DataTransfer\Tests\Unit\Helpers\Importers\Currency;

use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Currency\Importer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;

describe('Currency Importer', function () {
    beforeEach(function () {
        $this->loginAsAdmin();
    });

    it('should validate currency row correctly', function () {
        $importer = app(Importer::class);

        $rowData = [
            'code'    => 'USD',
            'symbol'  => '$',
            'decimal' => 2,
            'status'  => 1,
        ];

        // Setup import action append
        $import = JobTrack::factory()->create([
            'action' => Import::ACTION_APPEND,
        ]);
        $importer->setImport($import);
        $importer->setErrorHelper(app(Error::class));

        $isValid = $importer->validateRow($rowData, 1);

        expect($isValid)->toBeTrue();
    });

    it('should import currency batch correctly', function () {
        $importer = app(Importer::class);

        $import = JobTrack::factory()->create([
            'action' => Import::ACTION_APPEND,
        ]);

        $importer->setImport($import);

        $batchData = [
            [
                'code'    => 'TEST_USD',
                'symbol'  => '$',
                'decimal' => 2,
                'status'  => 1,
            ],
            [
                'code'    => 'TEST_EUR',
                'symbol'  => '€',
                'decimal' => 2,
                'status'  => 0,
            ],
        ];

        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $import->id,
            'data'         => $batchData,
        ]);

        $importer->importBatch($batch);

        $this->assertDatabaseHas('currencies', ['code' => 'TEST_USD', 'status' => 1]);
        $this->assertDatabaseHas('currencies', ['code' => 'TEST_EUR', 'status' => 0]);

        // Cleanup
        app(CurrencyRepository::class)->deleteWhere([['code', 'LIKE', 'TEST_%']]);
    });

    it('should respect status filter during import', function () {
        $importer = app(Importer::class);

        $import = JobTrack::factory()->create([
            'action' => Import::ACTION_APPEND,
        ]);

        // Mock jobInstance filters
        $import->jobInstance->update(['filters' => ['status' => 'enable']]);

        $importer->setImport($import);

        $batchData = [
            [
                'code'    => 'FILTER_USD',
                'symbol'  => '$',
                'decimal' => 2,
                'status'  => 1,
            ],
            [
                'code'    => 'FILTER_EUR',
                'symbol'  => '€',
                'decimal' => 2,
                'status'  => 0,
            ],
        ];

        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $import->id,
            'data'         => $batchData,
        ]);

        $importer->importBatch($batch);

        $this->assertDatabaseHas('currencies', ['code' => 'FILTER_USD']);
        $this->assertDatabaseMissing('currencies', ['code' => 'FILTER_EUR']);

        // Cleanup
        app(CurrencyRepository::class)->deleteWhere([['code', 'LIKE', 'FILTER_%']]);
    });

    it('should delete currencies when action is delete', function () {
        // Pre-create currency
        app(CurrencyRepository::class)->create([
            'code'   => 'DEL_USD',
            'symbol' => '$',
        ]);

        $importer = app(Importer::class);

        $import = JobTrack::factory()->create([
            'action' => Import::ACTION_DELETE,
        ]);

        $importer->setImport($import);

        $batchData = [
            ['code' => 'DEL_USD'],
        ];

        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $import->id,
            'data'         => $batchData,
        ]);

        $importer->importBatch($batch);

        $this->assertDatabaseMissing('currencies', ['code' => 'DEL_USD']);
    });
});

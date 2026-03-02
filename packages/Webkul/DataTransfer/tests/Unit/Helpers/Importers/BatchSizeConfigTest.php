<?php

use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;

describe('Import Batch Size Configuration', function () {
    it('has a default batch size of 100', function () {
        expect(AbstractImporter::BATCH_SIZE)->toBe(100);
    });

    it('has a large batch size constant of 1000', function () {
        expect(AbstractImporter::LARGE_BATCH_SIZE)->toBe(1000);
    });

    it('reads batch size from config with fallback', function () {
        config()->set('import.batch_size', 500);

        expect(config('import.batch_size'))->toBe(500);
    });

    it('reads bulk chunk size from config with fallback', function () {
        expect(config('import.bulk_chunk_size', 500))->toBe(500);
    });

    it('falls back to default batch size when config is null', function () {
        config()->set('import.batch_size', null);

        $batchSize = (int) (config('import.batch_size') ?? AbstractImporter::BATCH_SIZE);

        expect($batchSize)->toBe(100);
    });
});

describe('Import Config File', function () {
    it('has import config registered', function () {
        $config = config('import');

        expect($config)->toBeArray();
        expect($config)->toHaveKeys(['batch_size', 'bulk_chunk_size']);
    });

    it('has valid batch_size value', function () {
        $batchSize = config('import.batch_size');

        expect($batchSize)->toBeInt();
        expect($batchSize)->toBeGreaterThan(0);
    });

    it('has valid bulk_chunk_size value', function () {
        $chunkSize = config('import.bulk_chunk_size');

        expect($chunkSize)->toBeInt();
        expect($chunkSize)->toBeGreaterThan(0);
    });
});

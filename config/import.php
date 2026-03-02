<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Import Batch Size
    |--------------------------------------------------------------------------
    |
    | The number of rows to process per batch during import operations.
    | Larger batch sizes reduce the number of database round-trips and
    | queued jobs, significantly improving throughput for high-volume imports.
    |
    | Default: 100 (conservative)
    | Recommended for large imports: 1000-5000
    |
    | Adjust based on available memory and row complexity.
    |
    */

    'batch_size' => (int) env('IMPORT_BATCH_SIZE', 100),

    /*
    |--------------------------------------------------------------------------
    | Bulk Insert Chunk Size
    |--------------------------------------------------------------------------
    |
    | When using bulk DB operations (INSERT/UPSERT), rows are split into
    | chunks of this size. Keeps queries within MySQL's max_allowed_packet.
    |
    */

    'bulk_chunk_size' => (int) env('IMPORT_BULK_CHUNK_SIZE', 500),

    /*
    |--------------------------------------------------------------------------
    | MySQL Bulk Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, temporarily disables MySQL unique_checks and
    | foreign_key_checks during batch writes for 2-3x faster INSERTs/UPSERTs.
    | Safety checks are re-enabled after each batch completes.
    |
    | Safe to use when CSV has been validated beforehand (which UnoPim does).
    |
    */

    'mysql_bulk_mode' => (bool) env('IMPORT_MYSQL_BULK_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Parallel Validation
    |--------------------------------------------------------------------------
    |
    | When enabled, CSV validation is split across multiple CPU cores using
    | pcntl_fork. Each core validates a chunk of rows simultaneously.
    | Falls back to sequential if pcntl is unavailable.
    |
    | Minimum row count to trigger parallel validation: 1000 rows.
    | Below that threshold, sequential validation is faster.
    |
    */

    'parallel_validation' => (bool) env('IMPORT_PARALLEL_VALIDATION', true),

    /*
    |--------------------------------------------------------------------------
    | Validation Workers
    |--------------------------------------------------------------------------
    |
    | Number of parallel validation workers (CPU cores) to use.
    | Set to 0 for auto-detection (uses all available cores).
    |
    */

    'validation_workers' => (int) env('IMPORT_VALIDATION_WORKERS', 0),

    /*
    |--------------------------------------------------------------------------
    | Deferred Indexing
    |--------------------------------------------------------------------------
    |
    | When enabled, ElasticSearch indexing is skipped during import.
    | Run `php artisan unopim:product:index` after import to index products.
    |
    | Dramatically speeds up large imports by removing ES overhead per batch.
    |
    */

    'deferred_indexing' => (bool) env('IMPORT_DEFERRED_INDEXING', false),

];

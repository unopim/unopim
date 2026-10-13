<?php

return [
    /**
     * Maximum uncompressed size (in bytes) allowed for a single image entry
     * inside an uploaded images ZIP. Each accepted entry is read into memory for
     * MIME validation, so this bounds peak memory per entry and guards against
     * zip-bomb / memory-exhaustion. Defaults to 15 MB, comfortably above any
     * realistic product image; raise it per infrastructure if larger images are
     * imported.
     */
    'max_entry_size' => env('IMAGE_IMPORT_MAX_ENTRY_SIZE', 15728640),

    'max_entries' => env('IMAGE_IMPORT_MAX_ENTRIES', 10000),

    'max_total_size' => env('IMAGE_IMPORT_MAX_TOTAL_SIZE', 524288000),

    'max_compression_ratio' => env('IMAGE_IMPORT_MAX_COMPRESSION_RATIO', 100),

    'allowed_extensions' => [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff', 'jfif', 'jif',
        'pdf', 'doc', 'docx', 'ppt', 'pptx', 'rtf', 'txt', 'csv',
        'mp3', 'wav',
    ],

    'extension_mime_fallbacks' => [
        'csv' => ['text/plain'],
        'jif' => ['image/jpeg'],
    ],

    'max_reported_skipped' => env('IMAGE_IMPORT_MAX_REPORTED_SKIPPED', 50),
];

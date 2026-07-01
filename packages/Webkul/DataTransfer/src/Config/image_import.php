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
];

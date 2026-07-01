<?php

return [
    /**
     * Maximum uncompressed size (in bytes) allowed for a single image entry
     * inside an uploaded images ZIP. Guards against zip-bomb / memory-exhaustion
     * while staying at least as large as the whole-ZIP upload limit so that no
     * legitimate single image is ever rejected. Tune per infrastructure.
     */
    'max_entry_size' => env('IMAGE_IMPORT_MAX_ENTRY_SIZE', 104857600),
];

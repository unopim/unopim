<?php

return [
    'gallery' => [
        'min_files'                  => env('MEDIA_GALLERY_MIN_FILES', 0),
        'max_files'                  => env('MEDIA_GALLERY_MAX_FILES', 50),
        'max_file_size_kilobytes'    => env('MEDIA_GALLERY_MAX_FILE_SIZE_KB', 15360),
        'max_total_size_kilobytes'   => env('MEDIA_GALLERY_MAX_TOTAL_SIZE_KB', 51200),
    ],
];

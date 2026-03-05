<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Completeness Queue
    |--------------------------------------------------------------------------
    |
    | The queue name for completeness calculation jobs. Set to a dedicated
    | queue name (e.g. "completeness") and run a separate worker for it:
    |
    |   php artisan queue:work --queue=completeness
    |
    | This decouples completeness from the import pipeline so imports run
    | at full speed without waiting for score calculations.
    |
    */
    'queue' => env('COMPLETENESS_QUEUE', 'completeness'),
];

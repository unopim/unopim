<?php

return [
    /*
     * Product mass actions (delete / status update) run synchronously up to
     * this many selected products; larger selections are handed to the queue
     * so the admin request does not block on the per-product events.
     */
    'mass_action_async_threshold' => env('PRODUCT_MASS_ACTION_ASYNC_THRESHOLD', 200),
];

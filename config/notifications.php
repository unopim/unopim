<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notifications Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for dispatching admin notifications. Backed by config so it
    | survives config:cache (a bare env() read would be ignored once cached).
    |
    */

    'enabled' => env('NOTIFICATIONS_ENABLED', true),

];

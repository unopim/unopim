<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => 'v1/rest',
    'middleware' => [
        'accept.json',
        'auth:api',
        'throttle:rest-api',
        'api.scope',
        'request.locale',
    ],
], function (): void {

    require __DIR__.'/measurement-routes.php';

});

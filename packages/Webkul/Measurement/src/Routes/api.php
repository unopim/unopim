<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => 'rest',
    'middleware' => [
        'auth:api',
        'api.scope',
        'accept.json',
        'request.locale',
    ],
], function () {

    require 'measurement-routes.php';

});

<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix'     => 'v1/rest',
    'middleware' => [
        'auth:api',
        'api.scope',
        'accept.json',
        'request.locale',
    ],
], function () {
    /**
     * Settings API
     */
    require 'V1/settings-routes.php';

    /**
     * Catalog API
     */
    require 'V1/catalog-routes.php';

});

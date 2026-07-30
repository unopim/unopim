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
        'cache.headers:private;etag',
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

    /**
     * Product Passport API
     */
    require 'V1/passport-routes.php';

});

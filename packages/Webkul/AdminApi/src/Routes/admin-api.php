<?php

declare(strict_types=1);

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
    require __DIR__.'/V1/settings-routes.php';

    /**
     * Catalog API
     */
    require __DIR__.'/V1/catalog-routes.php';

});

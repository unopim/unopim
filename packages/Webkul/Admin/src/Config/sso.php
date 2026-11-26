<?php

use Webkul\Admin\Sso\MicrosoftProvider;
use Webkul\Admin\Sso\OidcProvider;

return [
    'providers' => [
        'microsoft' => MicrosoftProvider::class,
        'oidc'      => OidcProvider::class,
    ],
];

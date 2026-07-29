<?php

use Webkul\Admin\Sso\MicrosoftProvider;

return [
    /**
     * Admin login SSO drivers, keyed by the code used in the sso/{provider} routes.
     * Packages register their own by merging into this key from their service provider.
     */
    'providers' => [
        'microsoft' => MicrosoftProvider::class,
    ],
];

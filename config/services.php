<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'microsoft_sso' => [
        'enabled'       => env('MICROSOFT_SSO_ENABLED', false),
        'tenant'        => env('MICROSOFT_SSO_TENANT', ''),
        'client_id'     => env('MICROSOFT_SSO_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_SSO_CLIENT_SECRET'),

        'allowed_tenants' => env('MICROSOFT_SSO_ALLOWED_TENANTS', ''),
    ],

    'oidc_sso' => [
        'enabled'                => env('UNOPIM_OIDC_ENABLED'),
        'issuer'                 => env('UNOPIM_OIDC_ISSUER'),
        'client_id'              => env('UNOPIM_OIDC_CLIENT_ID'),
        'client_secret'          => env('UNOPIM_OIDC_CLIENT_SECRET'),
        'scopes'                 => env('UNOPIM_OIDC_SCOPES'),
        'label'                  => env('UNOPIM_OIDC_LABEL'),
        'require_verified_email' => env('UNOPIM_OIDC_REQUIRE_VERIFIED_EMAIL'),
        'auto_provision'         => env('UNOPIM_OIDC_AUTO_PROVISION'),
        'default_role'           => env('UNOPIM_OIDC_DEFAULT_ROLE'),
        'groups_claim'           => env('UNOPIM_OIDC_GROUPS_CLAIM'),
        'role_map'               => env('UNOPIM_OIDC_ROLE_MAP'),
        'rp_logout'              => env('UNOPIM_OIDC_RP_LOGOUT'),
        'sso_only'               => env('UNOPIM_OIDC_ONLY'),
    ],

];

<?php

use Webkul\User\Models\Admin;

return [

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Client UUIDs
    |--------------------------------------------------------------------------
    |
    | By default, Passport uses the auto-incrementing database IDs when
    | assigning IDs to clients. However, if you prefer to use UUIDs for
    | Passport client IDs, uncomment the line below.
    |
    */

    // 'client_uuids' => true,

    /*
    |--------------------------------------------------------------------------
    | Personal Access Client
    |--------------------------------------------------------------------------
    |
    | If you enable Passport's implicit grant tokens or password grant tokens,
    | you may want to specify a model that should be considered the "personal
    | access" client. This client will be used when a user requests a token
    | without specifying which client to use.
    |
    | "provider" should reference a provider defined in your "auth" config.
    |
    */

    'personal_access_client' => [
        'id'     => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Passport Storage
    |--------------------------------------------------------------------------
    |
    | This configuration option allows you to customize the storage of Passport
    | data. By default, the included "database" storage is used; however, you
    | are free to change this to any of the other supported storage options.
    |
    */

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'mysql'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Provider Model
    |--------------------------------------------------------------------------
    |
    | This setting specifies which user provider model Passport should use
    | for authenticating users. By default, Laravel looks for App\Models\User,
    | but Unopim uses Webkul\User\Models\Admin for admin users.
    |
    | This ensures the OAuth2 password grant flow uses the correct model.
    |
    */

    'user_model' => Admin::class,

];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Testing Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for tenant testing utilities.
    | These settings control how tenant testing works in your application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Tenant Testing Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default testing driver for tenant operations.
    | Options: 'database', 'memory', 'custom'
    |
    */
    'driver' => env('TENANT_TESTING_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Automatically Create Test Databases
    |--------------------------------------------------------------------------
    |
    | When enabled, test tenant databases will be automatically created.
    | Disable this if you want to manage database creation manually.
    |
    */
    'auto_create_database' => env('TENANT_TESTING_AUTO_CREATE_DB', true),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Storage Path
    |--------------------------------------------------------------------------
    |
    | The path where tenant test databases are stored when using memory driver.
    |
    */
    'storage_path' => storage_path('framework/testing'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Testing Cleanup
    |--------------------------------------------------------------------------
    |
    | Automatically clean up tenant data after tests complete.
    | Options: 'always', 'on_failure', 'never'
    |
    */
    'cleanup' => env('TENANT_TESTING_CLEANUP', 'always'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation Mode
    |--------------------------------------------------------------------------
    |
    | How tenant data should be isolated between tests.
    | Options: 'complete', 'database', 'storage', 'none'
    |
    */
    'isolation' => env('TENANT_TESTING_ISOLATION', 'complete'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Testing Helpers
    |--------------------------------------------------------------------------
    |
    | Enable or disable various tenant testing helpers.
    |
    */
    'helpers' => [
        'auto_setup' => env('TENANT_TESTING_AUTO_SETUP', true),
        'auto_cleanup' => env('TENANT_TESTING_AUTO_CLEANUP', true),
        'database_assertions' => env('TENANT_TESTING_DB_ASSERTIONS', true),
        'storage_assertions' => env('TENANT_TESTING_STORAGE_ASSERTIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Factory States
    |--------------------------------------------------------------------------
    |
    | Default states to apply to tenant factories when creating test tenants.
    |
    */
    'default_states' => [
        'active',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Migration Paths
    |--------------------------------------------------------------------------
    |
    | Paths where tenant migrations are located.
    | These migrations will be run for each tenant during setup.
    |
    */
    'migration_paths' => [
        database_path('migrations/tenant'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Seeders
    |--------------------------------------------------------------------------
    |
    | Classes to seed with each tenant during setup.
    |
    */
    'seeders' => [
        \Webkul\Tenant\Services\TenantDemoSeeder::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Tenant Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for test tenants.
    |
    */
    'settings' => [
        'environment' => 'testing',
        'debug' => true,
        'cache_driver' => 'array',
        'queue_driver' => 'sync',
    ],
];
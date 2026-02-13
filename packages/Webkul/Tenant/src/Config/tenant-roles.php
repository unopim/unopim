<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locked Roles
    |--------------------------------------------------------------------------
    |
    | These four roles are seeded at tenant creation and cannot be edited
    | or deleted. Their permission_type and scope are enforced by the system.
    |
    */
    'locked_roles' => [
        'tenant-admin' => [
            'name'            => 'Tenant Administrator',
            'code'            => 'tenant-admin',
            'permission_type' => 'all',
            'scope'           => 'tenant',
            'description'     => 'Full access to all resources within the tenant boundary.',
        ],

        'tenant-user' => [
            'name'            => 'Tenant User',
            'code'            => 'tenant-user',
            'permission_type' => 'custom',
            'scope'           => 'tenant',
            'description'     => 'Configurable permissions scoped to the tenant.',
        ],

        'platform-operator' => [
            'name'            => 'Platform Operator',
            'code'            => 'platform-operator',
            'permission_type' => 'all',
            'scope'           => 'platform',
            'description'     => 'Cross-tenant access with full permissions and audit logging.',
        ],

        'support-agent' => [
            'name'            => 'Support Agent',
            'code'            => 'support-agent',
            'permission_type' => 'custom',
            'scope'           => 'platform',
            'description'     => 'Read-only cross-tenant access for support purposes.',
            'default_permissions' => [
                'dashboard',
                'catalog',
                'catalog.products',
                'catalog.categories',
                'catalog.category_fields',
                'catalog.attributes',
                'catalog.attribute_groups',
                'catalog.families',
                'history',
                'history.view',
                'data_transfer',
                'data_transfer.job_tracker',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform-Reserved Permission Prefixes
    |--------------------------------------------------------------------------
    |
    | ACL keys starting with any of these prefixes are only accessible to
    | platform-scope roles (tenant_id IS NULL). Tenant roles with
    | permission_type='all' will have these excluded automatically.
    |
    */
    'platform_reserved_prefixes' => [
        'platform.',
    ],
];

<?php

namespace Webkul\AdminApi\Support;

use Webkul\User\Models\Role;

class ApiRole
{
    public const NAME = 'API';

    /**
     * Ensure the least-privilege API role exists and return it.
     */
    public static function ensure(): Role
    {
        return Role::firstOrCreate(
            ['name' => self::NAME],
            [
                'description'     => 'System role for API integration robot accounts. No panel permissions.',
                'permission_type' => 'custom',
                'permissions'     => [],
            ],
        );
    }
}

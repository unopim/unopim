<?php

namespace Webkul\User;

class Bouncer
{
    /**
     * Checks if user allowed or not for certain action
     *
     * @param  string  $permission
     */
    public function hasPermission($permission): bool
    {
        if (auth()->guard('admin')->check()
        && auth()->guard('admin')->user()->role->permission_type == 'all') {
            return true;
        }

        return auth()->guard('admin')->check() && auth()->guard('admin')->user()->hasPermission($permission);
    }

    /**
     * Checks if user allowed or not for certain action
     *
     * @param  string  $permission
     */
    public static function allow($permission): void
    {
        abort_if(! auth()->guard('admin')->check()
        || ! auth()->guard('admin')->user()->hasPermission($permission), 403, 'This action is unauthorized');
    }
}

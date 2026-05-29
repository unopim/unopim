<?php

namespace Webkul\User;

class Bouncer
{
    /**
     * Checks if user allowed or not for certain action
     */
    public function hasPermission(string $permission): bool
    {
        if (auth()->guard('admin')->check()
        && auth()->guard('admin')->user()->role->permission_type == 'all') {
            return true;
        } elseif (! auth()->guard('admin')->check()
        || ! auth()->guard('admin')->user()->hasPermission($permission)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if user allowed or not for certain action
     */
    public static function allow(string $permission): void
    {
        if (
            ! auth()->guard('admin')->check()
            || ! auth()->guard('admin')->user()->hasPermission($permission)
        ) {
            abort(403, 'This action is unauthorized');
        }
    }
}

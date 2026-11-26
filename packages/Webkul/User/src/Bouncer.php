<?php

namespace Webkul\User;

use Webkul\Core\Platform\ModuleGate;

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
     * Whether the admin holds the ACL permission mapped to the route, or one of its
     * `also_authorizes` alternates. A route with no ACL entry is open to any admin.
     *
     * Shared by the Bouncer middleware and the sidebar menu so both enforce one rule.
     */
    public function canAccessRoute(?string $routeName): bool
    {
        $acl = resolve('acl');

        if (! $acl || ! $routeName || ! isset($acl->roles[$routeName])) {
            return true;
        }

        $gate = resolve(ModuleGate::class);

        foreach ($acl->alternateRoles[$routeName] ?? [] as $permission) {
            if ($gate->allowsKey($permission) && $this->hasPermission($permission)) {
                return true;
            }
        }

        return $this->hasPermission($acl->roles[$routeName]);
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

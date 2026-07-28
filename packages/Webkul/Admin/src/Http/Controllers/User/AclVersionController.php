<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\User\Models\Admin;

class AclVersionController extends Controller
{
    /**
     * Fingerprint of the signed-in admin's effective permissions.
     *
     * A page renders the buttons its permissions allowed at the time it was
     * served. If an administrator edits the role afterwards, an already-open tab
     * keeps showing controls the user no longer holds — the server rejects them,
     * but the stale UI invites the click. Tabs poll this and reload themselves
     * when the fingerprint moves.
     */
    public function show(): JsonResponse
    {
        return new JsonResponse(['version' => self::fingerprint(auth()->guard('admin')->user())]);
    }

    /**
     * Stable hash of the permission set a page was rendered against.
     */
    public static function fingerprint(?Admin $admin): string
    {
        $role = $admin?->role;

        if (! $role) {
            return '';
        }

        $permissions = $role->permissions ?? [];

        sort($permissions);

        return sha1($role->permission_type.'|'.implode(',', $permissions));
    }
}

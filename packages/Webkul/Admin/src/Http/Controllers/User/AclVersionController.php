<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\User\Models\Admin;

class AclVersionController extends Controller
{
    public function show(): JsonResponse
    {
        return new JsonResponse(['version' => self::fingerprint(auth()->guard('admin')->user())]);
    }

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

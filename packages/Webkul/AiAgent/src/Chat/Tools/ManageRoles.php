<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ManageRoles implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('manage_roles')
            ->for('List roles and their permissions.')
            ->withEnumParameter('action', 'Action', ['list', 'details'])
            ->withStringParameter('name', 'Role name (for details)')
            ->using(function (string $action = 'list', ?string $name = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'settings.roles')) {
                    return $denied;
                }

                if ($action === 'list') {
                    $roles = DB::table('roles')
                        ->select('id', 'name', 'description', 'permission_type')
                        ->get();

                    $rolesWithCount = $roles->map(function ($role) {
                        $userCount = DB::table('admins')->where('role_id', $role->id)->count();

                        return [
                            'id'              => $role->id,
                            'name'            => $role->name,
                            'description'     => $role->description,
                            'permission_type' => $role->permission_type,
                            'user_count'      => $userCount,
                        ];
                    });

                    return json_encode(['roles' => $rolesWithCount->toArray()]);
                }

                if ($action === 'details' && $name) {
                    $role = DB::table('roles')->where('name', $name)->first();
                    if (! $role) {
                        return json_encode(['error' => "Role '{$name}' not found"]);
                    }

                    $permissions = json_decode($role->permissions, true) ?? [];

                    return json_encode([
                        'role' => [
                            'id'              => $role->id,
                            'name'            => $role->name,
                            'description'     => $role->description,
                            'permission_type' => $role->permission_type,
                            'permissions'     => $permissions,
                        ],
                    ]);
                }

                return json_encode(['error' => 'Invalid action']);
            });
    }
}

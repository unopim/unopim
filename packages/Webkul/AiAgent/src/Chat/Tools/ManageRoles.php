<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ManageRoles implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'manage_roles';
            }

            public function description(): string
            {
                return 'List roles and their permissions.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'action' => $schema->string()->enum(['list', 'details'])->description('Action'),
                    'name'   => $schema->string()->description('Role name (for details)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'settings.roles')) {
                    return $denied;
                }

                $action = $request->string('action')->toString() ?: 'list';
                $name = $request->string('name')->toString() ?: null;

                if ($action === 'list') {
                    $roles = DB::table('roles')
                        ->select('id', 'name', 'description', 'permission_type')
                        ->get();

                    $rolesWithCount = $roles->map(function ($role): array {
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

                    $permissions = json_decode((string) $role->permissions, true) ?? [];

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
            }
        };
    }
}

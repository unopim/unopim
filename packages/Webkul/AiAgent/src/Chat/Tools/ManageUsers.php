<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ManageUsers implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('manage_users')
            ->for('List or inspect admin users.')
            ->withEnumParameter('action', 'Action', ['list', 'details'])
            ->withStringParameter('email', 'User email (for details)')
            ->using(function (string $action = 'list', ?string $email = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'settings.users')) {
                    return $denied;
                }

                if ($action === 'list') {
                    $users = DB::table('admins as a')
                        ->leftJoin('roles as r', 'r.id', '=', 'a.role_id')
                        ->select('a.id', 'a.name', 'a.email', 'a.status', 'r.name as role')
                        ->orderBy('a.id')
                        ->limit(50)
                        ->get()
                        ->map(fn ($u) => $this->maskUserData($u));

                    return json_encode(['users' => $users->toArray()]);
                }

                if ($action === 'details' && $email) {
                    $user = DB::table('admins as a')
                        ->leftJoin('roles as r', 'r.id', '=', 'a.role_id')
                        ->where('a.email', $email)
                        ->select('a.id', 'a.name', 'a.email', 'a.status', 'r.name as role')
                        ->first();

                    if (! $user) {
                        return json_encode(['error' => "User '{$email}' not found"]);
                    }

                    return json_encode(['user' => (array) $this->maskUserData($user)]);
                }

                return json_encode(['error' => 'Invalid action']);
            });
    }

    /**
     * Mask email addresses to prevent data exposure via AI responses.
     */
    private function maskUserData(object $user): object
    {
        if (! empty($user->email)) {
            $atPos = strpos($user->email, '@');
            $user->email = $atPos > 2
                ? substr($user->email, 0, 2).str_repeat('*', $atPos - 2).substr($user->email, $atPos)
                : str_repeat('*', $atPos).substr($user->email, $atPos);
        }

        return $user;
    }
}

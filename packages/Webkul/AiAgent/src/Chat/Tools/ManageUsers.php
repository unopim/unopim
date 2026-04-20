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
     * Shows first 2 and last 2 characters of the local part with a
     * visible block of asterisks so users can tell the email is masked.
     */
    private function maskUserData(object $user): object
    {
        if (! empty($user->email)) {
            $atPos = strpos($user->email, '@');
            $local = substr($user->email, 0, $atPos);
            $domain = substr($user->email, $atPos);
            $len = strlen($local);

            $user->email = match (true) {
                $len <= 2 => str_repeat('*', 6).$domain,
                $len <= 4 => substr($local, 0, 2).str_repeat('*', 6).$domain,
                default   => substr($local, 0, 2).str_repeat('*', 6).substr($local, -2).$domain,
            };
        }

        return $user;
    }
}

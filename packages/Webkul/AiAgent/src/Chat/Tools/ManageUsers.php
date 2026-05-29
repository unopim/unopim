<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ManageUsers implements PimTool
{
    /**
     * Mask email addresses to prevent data exposure via AI responses.
     * Shows first 2 and last 2 characters of the local part with a
     * visible block of asterisks so users can tell the email is masked.
     */
    public function maskUserData(object $user): object
    {
        if (! empty($user->email)) {
            $atPos = strpos((string) $user->email, '@');
            $local = substr((string) $user->email, 0, $atPos);
            $domain = substr((string) $user->email, $atPos);
            $len = strlen($local);

            $user->email = match (true) {
                $len <= 2 => str_repeat('*', 6).$domain,
                $len <= 4 => substr($local, 0, 2).str_repeat('*', 6).$domain,
                default   => substr($local, 0, 2).str_repeat('*', 6).substr($local, -2).$domain,
            };
        }

        return $user;
    }

    public function register(ChatContext $context): Tool
    {
        $outer = $this;

        return new class($context, $outer) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(ChatContext $context, protected ManageUsers $outer)
            {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'manage_users';
            }

            public function description(): string
            {
                return 'List or inspect admin users.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'action' => $schema->string()->enum(['list', 'details'])->description('Action'),
                    'email'  => $schema->string()->description('User email (for details)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'settings.users')) {
                    return $denied;
                }

                $action = $request->string('action')->toString() ?: 'list';
                $email = $request->string('email')->toString() ?: null;

                if ($action === 'list') {
                    $users = DB::table('admins as a')
                        ->leftJoin('roles as r', 'r.id', '=', 'a.role_id')
                        ->select('a.id', 'a.name', 'a.email', 'a.status', 'r.name as role')
                        ->orderBy('a.id')
                        ->limit(50)
                        ->get()
                        ->map(fn (object $u) => $this->outer->maskUserData($u));

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

                    return json_encode(['user' => (array) $this->outer->maskUserData($user)]);
                }

                return json_encode(['error' => 'Invalid action']);
            }
        };
    }
}

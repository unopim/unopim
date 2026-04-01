<?php

namespace Webkul\AiAgent\Chat\Concerns;

use Webkul\AiAgent\Chat\ChatContext;

/**
 * Provides ACL permission checking for PIM tools.
 *
 * Tools use this trait to gate operations behind the authenticated
 * user's role permissions, preventing unauthorized catalog modifications.
 */
trait ChecksPermission
{
    /**
     * Check if the user has permission, returning a JSON error string if denied.
     *
     * @return string|null null if allowed, JSON error string if denied
     */
    protected function denyUnlessAllowed(ChatContext $context, string $permission): ?string
    {
        if (! $context->hasPermission($permission)) {
            return json_encode([
                'error' => "Permission denied: you do not have '{$permission}' access. Contact your administrator.",
            ]);
        }

        return null;
    }
}

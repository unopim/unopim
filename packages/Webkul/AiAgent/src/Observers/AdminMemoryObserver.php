<?php

namespace Webkul\AiAgent\Observers;

use Illuminate\Support\Facades\DB;
use Webkul\User\Contracts\Admin;

/**
 * Removes the agent memories an admin owns before the admin row is deleted.
 *
 * The memories foreign key nulls the owner on delete, and a memory without an
 * owner is read as shared by every admin's agent, so a private memory must go
 * with its author instead of outliving them.
 */
class AdminMemoryObserver
{
    /**
     * Delete the admin's own memories before the admin is deleted.
     */
    public function deleting(Admin $admin): void
    {
        DB::table('ai_agent_memories')->where('user_id', $admin->getKey())->delete();
    }
}

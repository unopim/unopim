<?php

namespace Webkul\Webhook\Listeners;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Webkul\User\Contracts\Admin;
use Webkul\User\Models\AdminProxy;

class ImportBatch
{
    /**
     * Set current user for this request which will be used while adding logs for the webhook
     */
    public function handle(mixed $batch): void
    {
        $userId = $batch->jobTrack->user_id ?? null;

        if ($userId) {
            $this->setAuthForRequest($userId);
        }
    }

    /**
     * Set the authenticated user for the current request.
     *
     * @param  \Admin|int  $user
     */
    public function setAuthForRequest(int|Admin $user): void
    {
        if (is_int($user)) {
            $user = AdminProxy::find($user);
        }

        if ($user instanceof Admin) {
            Auth::login($user);
        }
    }
}

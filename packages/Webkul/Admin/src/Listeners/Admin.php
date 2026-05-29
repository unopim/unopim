<?php

declare(strict_types=1);

namespace Webkul\Admin\Listeners;

use Illuminate\Support\Facades\Mail;

class Admin
{
    /**
     * Send mail on updating password.
     */
    public function afterPasswordUpdated(\Webkul\User\Models\Admin $admin): void {}
}

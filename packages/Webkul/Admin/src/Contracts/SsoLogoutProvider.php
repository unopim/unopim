<?php

namespace Webkul\Admin\Contracts;

use Illuminate\Http\Request;

interface SsoLogoutProvider
{
    /**
     * URL at the identity provider that ends its session too, or null when the
     * current admin session was not opened through this provider. Called before
     * the local session ends, so the driver can still read what it stored there.
     */
    public function logoutUrl(Request $request): ?string;
}

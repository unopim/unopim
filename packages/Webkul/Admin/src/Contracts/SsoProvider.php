<?php

namespace Webkul\Admin\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Sso\SsoIdentity;

interface SsoProvider
{
    /**
     * Unique driver code, used as the {provider} route segment.
     */
    public function getCode(): string;

    /**
     * Translated label rendered on the sign-in button.
     */
    public function getLabel(): string;

    /**
     * Blade view rendering the provider logo, or null for no icon.
     */
    public function getIconView(): ?string;

    /**
     * Whether the driver is switched on and fully credentialed.
     */
    public function isEnabled(): bool;

    /**
     * URL the sign-in button points at.
     */
    public function getRedirectUrl(): string;

    /**
     * Redirect URI handed to the identity provider.
     */
    public function getCallbackUrl(): string;

    /**
     * Send the visitor to the identity provider.
     */
    public function redirect(Request $request): RedirectResponse;

    /**
     * Verify the callback and resolve the authenticated identity,
     * or null when the exchange fails for any reason.
     */
    public function resolveIdentity(Request $request): ?SsoIdentity;

    /**
     * Ordering weight among sign-in buttons.
     */
    public function getSort(): int;
}

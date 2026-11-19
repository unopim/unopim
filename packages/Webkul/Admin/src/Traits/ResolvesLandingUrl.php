<?php

namespace Webkul\Admin\Traits;

use Illuminate\Support\Facades\Route;
use Webkul\Core\Platform\ModuleGate;

trait ResolvesLandingUrl
{
    /**
     * Resolve the landing URL for the authenticated admin by walking the sorted
     * menu config and returning the first item whose ACL key the user owns.
     *
     * A menu item's own key is not enough: the destination route is gated by its
     * own ACL key (menu key "settings" routes to admin.settings.locales.index,
     * gated by "settings.locales"), so both are checked. The acl config sweep
     * afterwards covers roles that grant only child-level keys. Entries of a
     * switched-off module are skipped. Falls back to logging the user out when
     * no entry is reachable.
     */
    protected function firstAllowedUrl(): string
    {
        $items = array_filter(
            config('menu.admin') ?? [],
            fn ($item) => ! empty($item['key']) && ! str_contains($item['key'], '.'),
        );

        usort($items, fn ($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

        $aclRoutes = optional(app('acl'))->roles ?? [];

        $gate = resolve(ModuleGate::class);

        foreach ($items as $item) {
            if (empty($item['route']) || ! Route::has($item['route'])) {
                continue;
            }

            if (! $gate->allowsKey($item['key']) || ! $gate->allowsRoute($item['route'])) {
                continue;
            }

            if (! bouncer()->hasPermission($item['key'])) {
                continue;
            }

            $routeAclKey = $aclRoutes[$item['route']] ?? $item['key'];

            if (! bouncer()->hasPermission($routeAclKey)) {
                continue;
            }

            return route($item['route']);
        }

        foreach (config('acl') ?? [] as $aclItem) {
            if (empty($aclItem['route']) || ! Route::has($aclItem['route'])) {
                continue;
            }

            if (! $gate->allowsKey($aclItem['key']) || ! $gate->allowsRoute($aclItem['route'])) {
                continue;
            }

            if (bouncer()->hasPermission($aclItem['key'])) {
                return route($aclItem['route']);
            }
        }

        auth()->guard('admin')->logout();

        session()->flash('error', trans('admin::app.errors.403.message'));

        return route('admin.session.create');
    }
}

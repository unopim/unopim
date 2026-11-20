<?php

namespace Webkul\Admin\Traits;

use Illuminate\Support\Facades\Route;

trait ResolvesLandingUrl
{
    /**
     * Resolve the landing URL for the authenticated admin by walking the sorted
     * menu config and returning the first item whose ACL key the user owns.
     *
     * A menu item's own key is not enough: the destination route is gated by its
     * own ACL key (menu key "settings" routes to admin.settings.locales.index,
     * gated by "settings.locales"), so both are checked. The acl config sweep
     * afterwards covers roles that grant only child-level keys. Routes a plain
     * redirect cannot reach (non-GET or with required parameters) are skipped.
     * Falls back to logging the user out when no entry is reachable.
     */
    protected function firstAllowedUrl(): string
    {
        $items = array_filter(
            config('menu.admin') ?? [],
            fn ($item) => ! empty($item['key']) && ! str_contains($item['key'], '.'),
        );

        usort($items, fn ($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

        $aclRoutes = optional(app('acl'))->roles ?? [];

        foreach ($items as $item) {
            if ($this->canLandOn($item, $aclRoutes)) {
                return route($item['route']);
            }
        }

        foreach (config('acl') ?? [] as $aclItem) {
            if ($this->canLandOn($aclItem, $aclRoutes)) {
                return route($aclItem['route']);
            }
        }

        auth()->guard('admin')->logout();

        session()->flash('error', trans('admin::app.errors.403.message'));

        return route('admin.session.create');
    }

    /**
     * Determine whether the user may be sent to the item's route: it must be
     * reachable by a redirect and both the item's ACL key and the key gating
     * the route itself must be granted.
     *
     * @param  array{key?: string, route?: string}  $item
     * @param  array<string, string>  $aclRoutes
     */
    protected function canLandOn(array $item, array $aclRoutes): bool
    {
        if (empty($item['key']) || empty($item['route']) || ! $this->isLandableRoute($item['route'])) {
            return false;
        }

        return bouncer()->hasPermission($item['key'])
            && bouncer()->hasPermission($aclRoutes[$item['route']] ?? $item['key']);
    }

    /**
     * Determine whether the named route can be opened by a redirect: a GET
     * route with no required parameter in its URI or domain.
     */
    protected function isLandableRoute(string $name): bool
    {
        $route = Route::getRoutes()->getByName($name);

        return $route !== null
            && in_array('GET', $route->methods(), true)
            && preg_match('/\{\w+\}/', ($route->getDomain() ?? '').'/'.$route->uri()) === 0;
    }
}

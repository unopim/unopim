<?php

namespace Webkul\Admin\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

    /**
     * Consume the remembered page and return it when safe, else the landing URL.
     */
    protected function intendedOrLandingUrl(): string
    {
        return $this->safeIntendedUrl(session()->pull('url.intended')) ?? $this->firstAllowedUrl();
    }

    /**
     * Return the URL when it is safe to send a freshly signed-in admin to it:
     * same scheme, host and port as the application URL, no credentials, and
     * resolving to a GET route behind the admin middleware. Otherwise null.
     */
    protected function safeIntendedUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        $target = parse_url($url);
        $app = parse_url((string) config('app.url'));

        if (
            ! is_array($target)
            || ! is_array($app)
            || isset($target['user'])
            || isset($target['pass'])
            || $this->originOf($target) !== $this->originOf($app)
        ) {
            return null;
        }

        $basePath = rtrim($app['path'] ?? '', '/');
        $path = $target['path'] ?? '/';

        if ($basePath !== '') {
            if (! str_starts_with($path, $basePath.'/')) {
                return null;
            }

            $path = substr($path, strlen($basePath));
        }

        try {
            $route = Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (HttpExceptionInterface) {
            return null;
        }

        return in_array('admin', $route->gatherMiddleware(), true) ? $url : null;
    }

    /**
     * Build a comparable scheme://host:port origin, filling in the default port.
     *
     * @param  array{scheme?: string, host?: string, port?: int}  $parts
     */
    private function originOf(array $parts): string
    {
        $scheme = strtolower($parts['scheme'] ?? '');

        $port = $parts['port'] ?? match ($scheme) {
            'https' => 443,
            'http'  => 80,
            default => 0,
        };

        return $scheme.'://'.strtolower($parts['host'] ?? '').':'.$port;
    }
}

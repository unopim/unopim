<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;

class SessionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create(): View|RedirectResponse
    {
        if (auth()->guard('admin')->check()) {
            return redirect()->to($this->firstAllowedUrl());
        }

        $previous = url()->previous();
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $previousHost = parse_url($previous, PHP_URL_HOST);

        if ($previousHost === $appHost && str_contains($previous, 'admin')) {
            $intendedUrl = $previous;
        } else {
            $intendedUrl = null;
        }

        if ($intendedUrl) {
            session()->put('url.intended', $intendedUrl);
        }

        return view('admin::users.sessions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): RedirectResponse
    {
        $this->validate(request(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = request('remember');

        if (! auth()->guard('admin')->attempt(request(['email', 'password']), $remember)) {
            session()->flash('error', trans('admin::app.settings.users.login-error'));

            return redirect()->route('admin.session.create')->withInput(request()->only('email'));
        }

        if (! auth()->guard('admin')->user()->status) {
            session()->flash('warning', trans('admin::app.settings.users.activate-warning'));

            auth()->guard('admin')->logout();

            return redirect()->route('admin.session.create')->withInput(request()->only('email'));
        }

        return redirect()->intended($this->firstAllowedUrl());
    }

    /**
     * Resolve the landing URL for the authenticated admin by walking the sorted
     * menu config and returning the first item whose ACL key the user owns.
     * Falls back to logging the user out when no menu entry is accessible.
     */
    protected function firstAllowedUrl(): string
    {
        $items = array_filter(
            config('menu.admin') ?? [],
            fn ($item) => ! empty($item['key']) && ! str_contains($item['key'], '.'),
        );

        usort($items, fn ($a, $b) => ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0));

        // app('acl')->roles maps every admin route name to the ACL key that the
        // Bouncer middleware will actually enforce on it. We need to land users
        // on a route whose ACL key they hold — checking just the menu item's
        // key isn't enough (e.g. menu key "settings" routes to
        // admin.settings.locales.index which is gated by "settings.locales").
        $aclRoutes = optional(app('acl'))->roles ?? [];

        foreach ($items as $item) {
            if (empty($item['route']) || ! Route::has($item['route'])) {
                continue;
            }

            // Must hold the top-level menu permission (so the sidebar entry is
            // actually visible to them).
            if (! bouncer()->hasPermission($item['key'])) {
                continue;
            }

            // AND must hold the specific permission that gates the destination
            // route — otherwise the Bouncer middleware will 403 the redirect
            // and we'll just bounce the user back to the login screen.
            $routeAclKey = $aclRoutes[$item['route']] ?? $item['key'];
            if (! bouncer()->hasPermission($routeAclKey)) {
                continue;
            }

            return route($item['route']);
        }

        // Fallback: scan every ACL entry sorted by sort and land on the first
        // route whose key the user holds — covers roles that don't grant any
        // top-level menu key but do grant a child-level one.
        foreach (config('acl') ?? [] as $aclItem) {
            if (empty($aclItem['route']) || ! Route::has($aclItem['route'])) {
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     */
    public function destroy(): RedirectResponse
    {
        auth()->guard('admin')->logout();

        return redirect()->route('admin.session.create');
    }
}

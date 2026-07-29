<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Sso\SsoManager;
use Webkul\Admin\Traits\ResolvesLandingUrl;

class SessionController extends Controller
{
    use ResolvesLandingUrl;

    public function __construct(protected readonly SsoManager $ssoManager) {}

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

        return view('admin::users.sessions.create', [
            'ssoProviders' => $this->ssoManager->enabled(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): RedirectResponse|JsonResponse
    {
        $this->validate(request(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $wantsJson = request()->wantsJson();

        $remember = request('remember');

        if (! auth()->guard('admin')->attempt(request(['email', 'password']), $remember)) {
            $message = trans('admin::app.settings.users.login-error');

            if ($wantsJson) {
                return response()->json(['message' => $message], 401);
            }

            session()->flash('error', $message);

            return redirect()->route('admin.session.create')->withInput(request()->only('email'));
        }

        if (auth()->guard('admin')->user()->isApiUser()) {
            auth()->guard('admin')->logout();

            $message = trans('admin::app.settings.users.login-error');

            if ($wantsJson) {
                return response()->json(['message' => $message], 401);
            }

            session()->flash('error', $message);

            return redirect()->route('admin.session.create')->withInput(request()->only('email'));
        }

        if (! auth()->guard('admin')->user()->status) {
            auth()->guard('admin')->logout();

            $message = trans('admin::app.settings.users.activate-warning');

            if ($wantsJson) {
                return response()->json(['type' => 'warning', 'message' => $message], 403);
            }

            session()->flash('warning', $message);

            return redirect()->route('admin.session.create')->withInput(request()->only('email'));
        }

        if ($wantsJson) {
            return response()->json([
                'redirect_url' => session()->pull('url.intended', $this->firstAllowedUrl()),
            ]);
        }

        return redirect()->intended($this->firstAllowedUrl());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(): RedirectResponse
    {
        auth()->guard('admin')->logout();

        return redirect()->route('admin.session.create');
    }
}

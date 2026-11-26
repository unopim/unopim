<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\ForgetPasswordRequest;
use Webkul\Admin\Traits\ResolvesLandingUrl;

class ForgetPasswordController extends Controller
{
    use ResolvesLandingUrl;

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create(): View|RedirectResponse
    {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('admin.dashboard.index');
        }

        if ($intendedUrl = $this->safeIntendedUrl(url()->previous())) {
            session()->put('url.intended', $intendedUrl);
        }

        return view('admin::users.forget-password.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * The answer never reveals whether the account exists, and a mail failure is
     * only logged. The link is sent after the response, which also hides the
     * timing where the server flushes the response first (PHP-FPM, Octane).
     */
    public function store(ForgetPasswordRequest $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->only('email');

        dispatch(function () use ($credentials): void {
            try {
                $this->broker()->sendResetLink($credentials);
            } catch (\Throwable $e) {
                report($e);
            }
        })->afterResponse();

        $message = trans('admin::app.users.forget-password.create.reset-link-sent');

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        session()->flash('success', $message);

        return redirect()->route('admin.forget_password.create');
    }

    /**
     * Get the broker to be used during password reset.
     */
    public function broker(): PasswordBroker
    {
        return Password::broker('admins');
    }
}

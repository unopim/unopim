<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
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
     * The link is sent after the response and a mail failure is only logged, so
     * neither the answer nor its timing reveals whether the account exists.
     */
    public function store(): RedirectResponse|JsonResponse
    {
        $this->validate(request(), [
            'email' => 'required|email',
        ]);

        $credentials = request()->only('email');

        dispatch(function () use ($credentials): void {
            try {
                $this->broker()->sendResetLink($credentials);
            } catch (\Throwable $e) {
                report($e);
            }
        })->afterResponse();

        $message = trans('admin::app.users.forget-password.create.reset-link-sent');

        if (request()->wantsJson()) {
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

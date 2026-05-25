<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;

class ForgetPasswordController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create(): View|RedirectResponse
    {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('admin.dashboard.index');
        } else {
            $previous = url()->previous();
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $previousHost = parse_url($previous, PHP_URL_HOST);

            if ($previousHost === $appHost && str_contains($previous, 'admin')) {
                $intendedUrl = $previous;
            } else {
                $intendedUrl = route('admin.dashboard.index');
            }

            session()->put('url.intended', $intendedUrl);

            return view('admin::users.forget-password.create');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): RedirectResponse
    {
        try {
            $this->validate(request(), [
                'email' => 'required|email',
            ]);

            $this->broker()->sendResetLink(
                request(['email'])
            );

            session()->flash('success', trans('admin::app.users.forget-password.create.reset-link-sent'));

            return redirect()->route('admin.forget_password.create');
        } catch (\Exception $e) {
            session()->flash('error', trans('admin::app.users.forget-password.create.email-settings-error'));
            report($e);

            return redirect()->route('admin.forget_password.create');
        }
    }

    /**
     * Get the broker to be used during password reset.
     */
    public function broker(): PasswordBroker
    {
        return Password::broker('admins');
    }
}

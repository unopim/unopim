<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  string|null  $token
     */
    public function create($token = null): View
    {
        return view('admin::users.reset-password.create')->with([
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): RedirectResponse|JsonResponse
    {
        $this->validate(request(), [
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:'.config('admin.auth.password_min'),
        ]);

        $wantsJson = request()->wantsJson();

        try {
            $response = $this->broker()->reset(
                request(['email', 'password', 'password_confirmation', 'token']), function ($admin, $password) {
                    $this->resetPassword($admin, $password);
                }
            );

            if ($response == Password::PASSWORD_RESET) {
                if ($wantsJson) {
                    return response()->json(['redirect_url' => route('admin.dashboard.index')]);
                }

                return redirect()->route('admin.dashboard.index');
            }

            $error = trans('admin::app.users.reset-password.invalid-link');

            if ($wantsJson) {
                return response()->json(['errors' => ['email' => [$error]]], 422);
            }

            return back()
                ->withInput(request(['email']))
                ->withErrors([
                    'email' => $error,
                ]);
        } catch (\Exception $e) {
            report($e);

            $message = trans('admin::app.users.reset-password.invalid-link');

            if ($wantsJson) {
                return response()->json(['message' => $message], 500);
            }

            session()->flash('error', $message);

            return redirect()->back();
        }
    }

    /**
     * Reset the given admin's password.
     *
     * @param  CanResetPassword  $admin
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($admin, $password)
    {
        $admin->password = Hash::make($password);

        $admin->setRememberToken(Str::random(60));

        $admin->save();

        event(new PasswordReset($admin));

        // Mirror the login gate: never auto-establish a session for an API robot
        // or a deactivated account.
        if (! $admin->isApiUser() && $admin->status) {
            auth()->guard('admin')->login($admin);
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

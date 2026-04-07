<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\Response;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;

class SessionController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('admin.dashboard.index');
        }

        $previous = url()->previous();
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $previousHost = parse_url($previous, PHP_URL_HOST);

        if ($previousHost === $appHost && str_contains($previous, 'admin')) {
            $intendedUrl = $previous;
        } else {
            $intendedUrl = route('admin.dashboard.index');
        }

        session()->put('url.intended', $intendedUrl);

        return view('admin::users.sessions.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        $this->validate(request(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = request('remember');

        if (! auth()->guard('admin')->attempt(request(['email', 'password']), $remember)) {
            session()->flash('error', trans('admin::app.settings.users.login-error'));

            return redirect()->route('admin.session.create');
        }

        if (! auth()->guard('admin')->user()->status) {
            session()->flash('warning', trans('admin::app.settings.users.activate-warning'));

            auth()->guard('admin')->logout();

            return redirect()->route('admin.session.create');
        }

        return redirect()->intended(route('admin.dashboard.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy()
    {
        auth()->guard('admin')->logout();

        return redirect()->route('admin.session.create');
    }
}

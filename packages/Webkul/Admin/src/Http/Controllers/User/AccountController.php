<?php

namespace Webkul\Admin\Http\Controllers\User;

use Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Filesystem\FileStorer;

class AccountController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected FileStorer $fileStorer) {}

    /**
     * Show the form for creating a new resource.
     */
    public function edit(): View
    {
        $user = auth()->guard('admin')->user();

        return view('admin::account.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(): RedirectResponse
    {
        $user = auth()->guard('admin')->user();

        $this->validate(request(), [
            'name'             => 'required',
            'email'            => 'email|unique:admins,email,'.$user->id,
            'password'         => 'nullable|min:6|confirmed',
            'current_password' => 'required|min:6',
            'image.*'          => 'nullable|mimes:bmp,jpeg,jpg,png,webp,svg',
            'timezone'         => 'required',
            'ui_locale_id'     => 'required',
        ]);

        $data = request()->only([
            'name',
            'email',
            'password',
            'password_confirmation',
            'current_password',
            'image',
            'timezone',
            'ui_locale_id',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            session()->flash('warning', trans('admin::app.account.edit.invalid-password'));

            return redirect()->back();
        }

        $isPasswordChanged = false;

        if (! $data['password']) {
            unset($data['password']);
        } else {
            $isPasswordChanged = true;

            $data['password'] = bcrypt($data['password']);
        }

        if (request()->hasFile('image')) {
            $data['image'] = $this->fileStorer->store(
                path: 'admins'.DIRECTORY_SEPARATOR.$user->id,
                file: current(request()->file('image'))
            );
        } else {
            if (! isset($data['image'])) {
                if (! empty($data['image'])) {
                    Storage::delete($user->image);
                }

                $data['image'] = null;
            } else {
                $data['image'] = $user->image;
            }
        }

        $user->update($data);

        if ($isPasswordChanged) {
            // Revoke all Passport access tokens issued to this admin so previously-issued API
            // credentials stop working after a password change (security best practice).
            $user->tokens()->update(['revoked' => true]);

            Event::dispatch('admin.password.update.after', $user);
        }

        session()->flash('success', trans('admin::app.account.edit.update-success'));

        return back();
    }
}

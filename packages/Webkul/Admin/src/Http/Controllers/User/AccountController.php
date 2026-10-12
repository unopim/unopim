<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\AccountForm;
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

        return view('admin::settings.users.edit', [
            'user'                    => $user,
            'roles'                   => collect(),
            'canManage'               => false,
            'isSelf'                  => true,
            'requiresCurrentPassword' => true,
            'formId'                  => 'account-edit-form',
            'formAction'              => route('admin.account.update'),
            'pageTitle'               => trans('admin::app.account.edit.title'),
            'backUrl'                 => route('admin.dashboard.index'),
            'backLabel'               => trans('admin::app.account.edit.back-btn'),
            'saveLabel'               => trans('admin::app.account.edit.save-btn'),
        ]);
    }

    /**
     * Update the signed-in admin's own profile.
     *
     * The reload the redirect forces is what re-renders the whole panel in a
     * freshly saved UI locale — a bare success would leave the old language
     * on screen until the user reloads by hand.
     */
    public function update(AccountForm $request): JsonResponse
    {
        $user = auth()->guard('admin')->user();

        $data = $request->safe()->except(['current_password', 'password_confirmation']);

        $data['use_gravatar'] = $request->boolean('use_gravatar');

        $isPasswordChanged = false;

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $isPasswordChanged = true;

            $data['password'] = bcrypt($data['password']);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image = is_array($image) ? current($image) : $image;
            $extension = $image->guessExtension() ?: strtolower($image->getClientOriginalExtension());

            $data['image'] = $this->fileStorer->storeAs(
                path: 'admins'.DIRECTORY_SEPARATOR.$user->id,
                name: Str::random(40).'.'.$extension,
                file: $image,
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
            $user->tokens()->update(['revoked' => true]);

            Event::dispatch('admin.password.update.after', $user);
        }

        return new JsonResponse([
            'message'      => trans('admin::app.account.edit.update-success'),
            'redirect_url' => route('admin.account.edit'),
        ]);
    }
}

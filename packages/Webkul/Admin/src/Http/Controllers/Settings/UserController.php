<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Settings\UserDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\UserForm;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\User\Contracts\Admin;
use Webkul\User\Models\Role;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;
use Webkul\User\Services\RoleAccessGuard;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AdminRepository $adminRepository,
        protected RoleRepository $roleRepository,
        protected FileStorer $fileStorer,
        protected RoleAccessGuard $roleAccessGuard
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(UserDataGrid::class)->toJson();
        }

        $roles = $this->assignableRoles();

        return view('admin::settings.users.index', compact('roles'));
    }

    /**
     * Whether the acting admin is forbidden from assigning the given role.
     *
     * A full-access admin may assign anything; anyone else is blocked from
     * assigning an all-access role or any custom role carrying permissions they
     * do not themselves hold.
     */
    protected function cannotAssignRole(?Role $targetRole): bool
    {
        return $this->roleAssignmentError($targetRole) !== null;
    }

    /**
     * Whether the user is the last active admin whose role grants every permission.
     */
    protected function isLastActiveFullAccessAdmin(?Admin $user): bool
    {
        return $this->holdsActiveFullAccess($user)
            && ! $this->adminRepository->hasOtherActiveFullAccessAdmin(exceptAdminIds: [$user->id]);
    }

    /**
     * Whether the user is active and their role grants every permission.
     */
    protected function holdsActiveFullAccess(?Admin $user): bool
    {
        return $user instanceof Admin
            && (bool) $user->status
            && $this->roleAccessGuard->hasFullAccess($user->role);
    }

    /**
     * Translation key explaining why the acting admin cannot assign the role, or null when it can.
     */
    protected function roleAssignmentError(?Role $targetRole): ?string
    {
        return $this->roleAccessGuard->assignmentError(auth()->guard('admin')->user(), $targetRole);
    }

    /**
     * @return Collection<int, Role>
     */
    protected function assignableRoles(?int $keepRoleId = null): Collection
    {
        return $this->roleRepository->all()
            ->filter(fn ($role): bool => (int) $role->id === $keepRoleId || ! $this->cannotAssignRole($role))
            ->values();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserForm $request): JsonResponse
    {
        $data = $request->only([
            'name',
            'email',
            'password',
            'password_confirmation',
            'role_id',
            'ui_locale_id',
            'catalog_locale_id',
            'default_channel_id',
            'status',
            'timezone',
        ]);

        $data['use_gravatar'] = $request->boolean('use_gravatar', true);

        if ($error = $this->roleAssignmentError($this->roleRepository->find($data['role_id']))) {
            return new JsonResponse([
                'message' => trans($error),
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        if ($data['password'] ?? null) {
            $data['password'] = bcrypt($data['password']);

            $data['api_token'] = Str::random(80);
        }

        Event::dispatch('user.admin.create.before');

        $admin = $this->adminRepository->create($data);

        if (request()->hasFile('image')) {
            $image = request()->file('image');
            $image = is_array($image) ? current($image) : $image;
            $extension = $image->guessExtension() ?: strtolower($image->getClientOriginalExtension());

            $admin->image = $this->fileStorer->storeAs(
                path: 'admins'.DIRECTORY_SEPARATOR.$admin->id,
                name: Str::random(40).'.'.$extension,
                file: $image,
            );

            $admin->save();
        }

        Event::dispatch('user.admin.create.after', $admin);

        $response = ['message' => trans('admin::app.settings.users.create-success')];

        if (bouncer()->hasPermission('settings.users.users.edit')) {
            $response['redirect_url'] = route('admin.settings.users.edit', $admin->id);
        }

        return new JsonResponse($response);
    }

    /**
     * User Details
     *
     * @param  int  $id
     */
    public function edit($id): View|JsonResponse
    {
        $user = $this->adminRepository->findOrFail($id);

        if ($user->isApiUser()) {
            abort(404);
        }

        $roles = $this->assignableRoles((int) $user->role_id);

        $timezone = ['id' => $user?->timezone, 'label' => $user?->timezone];

        if (! request()->expectsJson()) {
            return view('admin::settings.users.edit', [
                'user'                    => $user,
                'roles'                   => $roles,
                'canManage'               => true,
                'isSelf'                  => $user->id === auth()->guard('admin')->id(),
                'requiresCurrentPassword' => true,
                'formId'                  => 'user-edit-form',
                'formAction'              => route('admin.settings.users.update'),
                'pageTitle'               => trans('admin::app.settings.users.edit.title'),
                'backUrl'                 => route('admin.settings.users.index'),
                'backLabel'               => trans('admin::app.settings.users.edit.back-btn'),
                'saveLabel'               => trans('admin::app.settings.users.edit.save-btn'),
            ]);
        }

        return new JsonResponse([
            'roles'    => $roles,
            'user'     => [
                ...$user->toArray(),
                'status' => (bool) $user->status,
            ],
            'timezone' => $timezone,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserForm $request): JsonResponse
    {
        $id = (int) request()->id;

        $user = $this->adminRepository->findOrFail($id);

        if ($user->isApiUser()) {
            abort(404);
        }

        $data = $this->prepareUserData($request);

        $error = null;

        $admin = DB::transaction(function () use ($id, $user, $data, &$error): ?Admin {
            if ($this->holdsActiveFullAccess($user)) {
                $this->roleRepository->lockFullAccessRoles();

                $user = $this->adminRepository->find($id);
            }

            if ($error = $this->updateError($user, $data)) {
                return null;
            }

            Event::dispatch('user.admin.update.before', $id);

            return $this->adminRepository->update($data, $id);
        });

        if (! $admin) {
            return new JsonResponse(['message' => $error], JsonResponse::HTTP_FORBIDDEN);
        }

        if (request()->hasFile('image')) {
            $image = request()->file('image');
            $image = is_array($image) ? current($image) : $image;
            $extension = $image->guessExtension() ?: strtolower($image->getClientOriginalExtension());

            $admin->image = $this->fileStorer->storeAs(
                path: 'admins'.DIRECTORY_SEPARATOR.$admin->id,
                name: Str::random(40).'.'.$extension,
                file: $image,
            );
        } else {
            if (! request()->has('image') && $admin->image) {
                Storage::delete($admin->image);
                $admin->image = null;
            }
        }

        $admin->save();

        if (! empty($data['password'])) {
            Event::dispatch('admin.password.update.after', $admin);
        }

        Event::dispatch('user.admin.update.after', $admin);

        return new JsonResponse([
            'message'      => trans('admin::app.settings.users.update-success'),
            'redirect_url' => route('admin.settings.users.index'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     */
    public function destroy($id): JsonResponse
    {
        $user = $this->adminRepository->findOrFail($id);

        if ($user->isApiUser()) {
            abort(404);
        }

        if ($this->adminRepository->count() == 1) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.users.last-delete-error'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($id == auth('admin')->user()->id) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.users.current-user-delete-error'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($this->cannotAssignRole($user->role)) {
            return new JsonResponse([
                'message' => trans('admin::app.common.unauthorized'),
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            if (! $this->deleteUnlessLastActiveFullAccessAdmin((int) $id)) {
                return new JsonResponse([
                    'message' => trans('admin::app.settings.users.last-all-access-delete-error'),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            Event::dispatch('user.admin.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.settings.users.delete-success'),
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.users.delete-failed'),
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Show the form for confirming the user password.
     *
     * @param  int  $id
     */
    public function confirm($id): View
    {
        $user = $this->adminRepository->findOrFail($id);

        return view('admin::customers.customers.confirm-password', compact('user'));
    }

    /**
     * Destroy current after confirming.
     */
    public function destroySelf(): JsonResponse
    {
        $password = request()->input('password');

        if (Hash::check($password, auth()->guard('admin')->user()->password)) {
            if ($this->adminRepository->count() == 1) {
                return new JsonResponse([
                    'message' => trans('admin::app.settings.users.delete-last'),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $id = auth()->guard('admin')->user()->id;

            if (! $this->deleteUnlessLastActiveFullAccessAdmin($id)) {
                return new JsonResponse([
                    'message' => trans('admin::app.settings.users.last-all-access-delete-error'),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            Event::dispatch('user.admin.delete.after', $id);

            return new JsonResponse([
                'redirectUrl' => route('admin.session.create'),
                'message'     => trans('admin::app.settings.users.delete-success'),
            ]);
        } else {
            return new JsonResponse([
                'message' => trans('admin::app.settings.users.incorrect-password'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }

    /**
     * Delete the user, unless they are the last active admin with full access; checked and written under one lock.
     */
    private function deleteUnlessLastActiveFullAccessAdmin(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $this->roleRepository->lockFullAccessRoles();

            if ($this->isLastActiveFullAccessAdmin($this->adminRepository->find($id))) {
                return false;
            }

            Event::dispatch('user.admin.delete.before', $id);

            $this->adminRepository->delete($id);

            return true;
        });
    }

    /**
     * Request data for the user update, with the password hashed and the flags cast.
     *
     * @return array<string, mixed>
     */
    private function prepareUserData(UserForm $request): array
    {
        $data = $request->validated();

        unset($data['current_password']);

        if (! $data['password']) {
            unset($data['password']);
        } else {
            $data['password'] = bcrypt($data['password']);
        }

        $data['status'] = filter_var($data['status'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $data['use_gravatar'] = $request->boolean('use_gravatar', true);

        unset($data['image']);

        return $data;
    }

    /**
     * Message explaining why the acting admin may not apply the update to the user, or null when it may.
     *
     * @param  array<string, mixed>  $data
     */
    private function updateError(Admin $user, array $data): ?string
    {
        if ($this->cannotAssignRole($user->role)) {
            return $this->cannotChangeMessage('role');
        }

        if (! $data['status'] && (bool) $user->status && $this->isLastActiveFullAccessAdmin($user)) {
            return $this->cannotChangeMessage('status');
        }

        if ($this->cannotAssignRole($this->roleRepository->find($data['role_id'] ?? $user->role_id))) {
            return $this->cannotChangeMessage('role');
        }

        $isRoleChanged = isset($data['role_id']) && (int) $data['role_id'] !== (int) $user->role_id;

        if ($isRoleChanged && $this->isLastActiveFullAccessAdmin($user)) {
            return $this->cannotChangeMessage('role');
        }

        return null;
    }

    /**
     * Message refusing a change to the named user column.
     */
    private function cannotChangeMessage(string $columnName): string
    {
        return trans('admin::app.settings.users.cannot-change', ['name' => $columnName]);
    }
}

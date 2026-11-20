<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Settings\RolesDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\RoleForm;
use Webkul\Core\Platform\ModuleGate;
use Webkul\User\Contracts\Role;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;
use Webkul\User\Services\RoleAccessGuard;

class RoleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected RoleRepository $roleRepository,
        protected AdminRepository $adminRepository,
        protected RoleAccessGuard $roleAccessGuard,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(RolesDataGrid::class)->toJson();
        }

        return view('admin::settings.roles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin::settings.roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleForm $request): JsonResponse|RedirectResponse
    {
        Event::dispatch('user.role.create.before');

        $data = $request->only([
            'name',
            'description',
            'permission_type',
        ]);

        if (is_array($request->input('permissions'))) {
            $data['permissions'] = app(ModuleGate::class)->keepHiddenKeys($request->input('permissions'), []);
        }

        $role = $this->roleRepository->create($data);

        Event::dispatch('user.role.create.after', $role);

        return $this->respondSaved(
            trans('admin::app.settings.roles.create-success'),
            route('admin.settings.roles.index')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $role = $this->roleRepository->findOrFail($id);

        return view('admin::settings.roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleForm $request, int $id): JsonResponse|RedirectResponse
    {
        if ($error = $this->roleAccessGuard->currentAccessError(auth()->guard('admin')->user(), $this->roleRepository->findOrFail($id))) {
            return $this->respondError(trans($error), JsonResponse::HTTP_FORBIDDEN, route('admin.settings.roles.index'));
        }

        $data = $request->only([
            'name',
            'description',
            'permission_type',
        ]);

        $submittedPermissions = $request->has('permissions') ? (array) $request->permissions : [];

        $role = DB::transaction(function () use ($data, $submittedPermissions, $id): ?Role {
            $role = $this->roleRepository->lockForFullAccessChange($id);

            $data['permissions'] = app(ModuleGate::class)->keepHiddenKeys(
                $submittedPermissions,
                (array) ($role->permissions ?? []),
            );

            $losesFullAccess = $role->permission_type === RoleAccessGuard::FULL_ACCESS
                && $data['permission_type'] !== RoleAccessGuard::FULL_ACCESS;

            if ($losesFullAccess && ! $this->adminRepository->hasOtherActiveFullAccessAdmin([$role->id])) {
                return null;
            }

            Event::dispatch('user.role.update.before', $id);

            return $this->roleRepository->update($data, $id);
        });

        if (! $role) {
            return $this->respondError(
                trans('admin::app.settings.roles.last-full-access'),
                redirectUrl: route('admin.settings.roles.index')
            );
        }

        Event::dispatch('user.role.update.after', $role);

        return $this->respondSaved(trans('admin::app.settings.roles.update-success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $role = $this->roleRepository->findOrFail($id);

        if ($error = $this->roleAccessGuard->currentAccessError(auth()->guard('admin')->user(), $role)) {
            return new JsonResponse(['message' => trans($error)], JsonResponse::HTTP_FORBIDDEN);
        }

        if ($role->admins()->exists()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.roles.being-used-by', ['name' => $role->admins()->orderBy('id')->value('name')]),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($this->roleRepository->count() == 1) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.roles.last-delete-error'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            Event::dispatch('user.role.delete.before', $id);

            $this->roleRepository->delete($id);

            Event::dispatch('user.role.delete.after', $id);

            return $this->respondDeleted(trans('admin::app.settings.roles.delete-success'));
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.roles.delete-failed'),
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}

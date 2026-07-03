<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Settings\RolesDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\RoleForm;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

class RoleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected RoleRepository $roleRepository,
        protected AdminRepository $adminRepository
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

        $role = $this->roleRepository->create($request->only([
            'name',
            'description',
            'permission_type',
            'permissions',
        ]));

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
        $role = $this->roleRepository->findOrFail($id);

        /**
         * Check for other admins if the role has been changed from all to custom.
         */
        $isChangedFromAll = $request->permission_type == 'custom' && $role->permission_type == 'all';

        if ($isChangedFromAll && $role->admins->count() === 1 && $this->adminRepository->countAdminsWithAllAccess() === 1) {
            return $this->respondError(
                trans('admin::app.settings.roles.being-used'),
                redirectUrl: route('admin.settings.roles.index')
            );
        }

        $data = array_merge($request->only([
            'name',
            'description',
            'permission_type',
        ]), [
            'permissions' => $request->has('permissions') ? $request->permissions : [],
        ]);

        Event::dispatch('user.role.update.before', $id);

        $role = $this->roleRepository->update($data, $id);

        Event::dispatch('user.role.update.after', $role);

        return $this->respondSaved(trans('admin::app.settings.roles.update-success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $role = $this->roleRepository->findOrFail($id);

        if ($role->admins->count() >= 1) {
            return new JsonResponse(['message' => trans('admin::app.settings.roles.being-used-by', [
                'name' => $role->admins->first()?->toArray()['name'],
            ])], JsonResponse::HTTP_BAD_REQUEST);
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

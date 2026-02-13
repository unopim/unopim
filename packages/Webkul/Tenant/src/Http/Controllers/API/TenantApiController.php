<?php

namespace Webkul\Tenant\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Repositories\TenantRepository;
use Webkul\Tenant\Services\TenantPurger;
use Webkul\Tenant\Services\TenantSeeder;

class TenantApiController extends Controller
{
    public function __construct(
        protected TenantRepository $tenantRepository,
        protected TenantSeeder $tenantSeeder,
        protected TenantPurger $tenantPurger,
    ) {}

    /**
     * List all tenants (paginated).
     *
     * Platform operators see all tenants.
     * Tenant-scoped users see only their own tenant.
     */
    public function index(): JsonResponse
    {
        $user = auth('api')->user();
        $query = Tenant::withoutGlobalScopes()->orderBy('id', 'desc');

        if ($user && $user->tenant_id) {
            $query->where('id', $user->tenant_id);
        }

        $tenants = $query->paginate(request('limit', 15));

        return new JsonResponse($tenants);
    }

    /**
     * Show a single tenant.
     */
    public function show(int $id): JsonResponse
    {
        $this->authorizeAccessToTenant($id);

        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);

        return new JsonResponse(['data' => $tenant]);
    }

    /**
     * Create a new tenant (platform operators only).
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if ($user && $user->tenant_id) {
            abort(403, 'Only platform operators can create tenants.');
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'domain'      => 'required|string|max:255|unique:tenants,domain',
            'admin_email' => 'required|email|max:255',
        ]);

        try {
            $tenant = Tenant::create([
                'uuid'          => (string) \Illuminate\Support\Str::uuid(),
                'name'          => $request->input('name'),
                'domain'        => $request->input('domain'),
                'status'        => Tenant::STATUS_PROVISIONING,
                'es_index_uuid' => (string) \Illuminate\Support\Str::uuid(),
            ]);

            $result = $this->tenantSeeder->seed($tenant, [
                'email' => $request->input('admin_email'),
            ]);

            $tenant->transitionTo(Tenant::STATUS_ACTIVE);

            return new JsonResponse([
                'data'        => $tenant->fresh(),
                'credentials' => [
                    'admin_email'    => $result['admin_email'],
                    'admin_password' => $result['admin_password'],
                    'client_id'      => $result['client_id'],
                    'client_secret'  => $result['client_secret'],
                ],
                'message' => 'Tenant created successfully.',
            ], 201);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'message' => 'Failed to create tenant: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a tenant.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAccessToTenant($id);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tenant = $this->tenantRepository->update(
            $request->only('name', 'settings'),
            $id,
        );

        return new JsonResponse([
            'data'    => $tenant,
            'message' => 'Tenant updated successfully.',
        ]);
    }

    /**
     * Delete a tenant.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->authorizeAccessToTenant($id);

        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);

        if ($tenant->status === Tenant::STATUS_PROVISIONING) {
            return new JsonResponse([
                'message' => 'Cannot delete a tenant still provisioning.',
            ], 400);
        }

        try {
            $tenant->transitionTo(Tenant::STATUS_DELETING);
            $this->tenantPurger->purge($tenant);
            $tenant->transitionTo(Tenant::STATUS_DELETED);

            return new JsonResponse(['message' => 'Tenant deleted successfully.']);
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => 'Delete failed.'], 500);
        }
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(int $id): JsonResponse
    {
        $this->authorizeAccessToTenant($id);

        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);

        try {
            $tenant->transitionTo(Tenant::STATUS_SUSPENDED);

            return new JsonResponse(['message' => 'Tenant suspended successfully.']);
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Activate a tenant.
     */
    public function activate(int $id): JsonResponse
    {
        $this->authorizeAccessToTenant($id);

        $tenant = Tenant::withoutGlobalScopes()->findOrFail($id);

        try {
            $tenant->transitionTo(Tenant::STATUS_ACTIVE);

            return new JsonResponse(['message' => 'Tenant activated successfully.']);
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Ensure the authenticated user is allowed to access the given tenant.
     *
     * Platform operators (tenant_id = null) may access any tenant.
     * Tenant-scoped users may only access their own tenant.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeAccessToTenant(int $id): void
    {
        $user = auth('api')->user();

        if ($user && $user->tenant_id && $user->tenant_id !== $id) {
            abort(403, 'Access denied to this tenant.');
        }
    }
}

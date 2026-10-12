<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\ProductGridViewForm;
use Webkul\Product\Repositories\ProductGridViewRepository;

class ProductGridViewController extends Controller
{
    public function __construct(protected ProductGridViewRepository $productGridViewRepository) {}

    /**
     * Saved views the current admin may apply: their own plus the shared ones.
     */
    public function index(): JsonResponse
    {
        $this->authorizeAccess();

        $adminId = $this->adminId();

        $search = trim((string) request('query', ''));

        $views = $this->productGridViewRepository->visibleTo($adminId, $search)
            ->map(fn ($view): array => [
                'id'        => $view->id,
                'name'      => $view->name,
                'is_shared' => $view->is_shared,
                'is_owner'  => $view->admin_id === $adminId,
                'payload'   => $view->payload,
            ])
            ->values();

        return new JsonResponse(['views' => $views]);
    }

    public function store(ProductGridViewForm $request): JsonResponse
    {
        $adminId = $this->adminId();

        $view = $this->productGridViewRepository->updateOrCreate(
            [
                'admin_id' => $adminId,
                'name'     => $request->input('name'),
            ],
            [
                'is_shared' => $request->boolean('is_shared'),
                'payload'   => $request->input('payload'),
            ]
        );

        return new JsonResponse([
            'message' => trans('admin::app.components.datagrid.filters.saved-filters.saved'),
            'view'    => [
                'id'        => $view->id,
                'name'      => $view->name,
                'is_shared' => $view->is_shared,
                'is_owner'  => true,
                'payload'   => $view->payload,
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorizeAccess();

        $view = $this->productGridViewRepository->find($id);

        if (! $view) {
            abort(404);
        }

        if ($view->admin_id !== $this->adminId()) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        $view->delete();

        return new JsonResponse([
            'message' => trans('admin::app.components.datagrid.filters.saved-filters.deleted'),
        ]);
    }

    protected function authorizeAccess(): void
    {
        if (! bouncer()->hasPermission('catalog.products')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }
    }

    protected function adminId(): int
    {
        return (int) auth()->guard('admin')->id();
    }
}

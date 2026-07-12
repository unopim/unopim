<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Catalog\AssociationTypeDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\AssociationTypeRequest;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Product\Repositories\AssociationTypeRepository;

class AssociationTypeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AssociationTypeRepository $associationTypeRepository,
        protected LocaleRepository $localeRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(AssociationTypeDataGrid::class)->toJson();
        }

        return view('admin::catalog.associations.types.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * Created from the code-only create modal on the index page: the type is
     * persisted with just its code, then the user is redirected to the edit
     * page to configure labels, per-link fields and everything else. The
     * `<x-admin::form ajax>` submit turns this redirect into a JSON
     * `redirect_url` automatically.
     */
    public function store(AssociationTypeRequest $associationTypeRequest): RedirectResponse|JsonResponse
    {
        $requestData = $associationTypeRequest->all();

        /**
         * An association type created from the admin is always user-defined; only the
         * seeder is allowed to mark a type as a default (is_user_defined = 0). New
         * types are active immediately so they surface on product edit right away.
         */
        $requestData['is_user_defined'] = 1;
        $requestData['status'] = $requestData['status'] ?? 1;

        /**
         * Position is auto-assigned to sort after every existing type when the
         * request omits it (the modal does not ask for one).
         */
        if (! isset($requestData['position']) || $requestData['position'] === '' || $requestData['position'] === null) {
            $requestData['position'] = ((int) $this->associationTypeRepository->max('position')) + 1;
        }

        /**
         * The modal collects no label. Seed the requested-locale name to the code
         * so the grid label is never blank; the user edits it on the edit page.
         */
        $localeCode = core()->getRequestedLocaleCode();

        if (empty($requestData[$localeCode]['name'] ?? null)) {
            $requestData[$localeCode]['name'] = $requestData['code'];
        }

        Event::dispatch('catalog.association_type.create.before');

        $associationType = $this->associationTypeRepository->create($requestData);

        Event::dispatch('catalog.association_type.create.after', $associationType);

        session()->flash('success', trans('admin::app.catalog.association_types.create-success'));

        return redirect()->route('admin.catalog.association_types.edit', $associationType->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        return view('admin::catalog.associations.types.edit', [
            'associationType' => $this->associationTypeRepository
                ->with(['translations', 'fields.translations', 'fields.options.translations'])
                ->findOrFail($id),
            'locales' => $this->localeRepository->getActiveLocales(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssociationTypeRequest $associationTypeRequest, int $id): RedirectResponse|JsonResponse
    {
        /**
         * `code` and `is_user_defined` are immutable once an association type is
         * created, so they are always stripped out before the update is applied.
         */
        $requestData = $associationTypeRequest->except(['code', 'is_user_defined']);

        Event::dispatch('catalog.association_type.update.before', $id);

        $associationType = $this->associationTypeRepository->update($requestData, $id);

        Event::dispatch('catalog.association_type.update.after', $associationType);

        session()->flash('success', trans('admin::app.catalog.association_types.update-success'));

        return redirect()->route('admin.catalog.association_types.edit', $id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findOrFail($id);

        if (! $associationType->is_user_defined) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.association_types.user-define-error'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            Event::dispatch('catalog.association_type.delete.before', $id);

            $this->associationTypeRepository->delete($id);

            Event::dispatch('catalog.association_type.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.association_types.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.association_types.delete-failed'),
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Remove the specified resources from database.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $indices = $massDestroyRequest->input('indices');

        $delete = false;

        foreach ($indices as $index) {
            $associationType = $this->associationTypeRepository->find($index);

            if (! $associationType || ! $associationType->is_user_defined) {
                continue;
            }

            Event::dispatch('catalog.association_type.delete.before', $index);

            $this->associationTypeRepository->delete($index);

            $delete = true;

            Event::dispatch('catalog.association_type.delete.after', $index);
        }

        if (! $delete) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.association_types.index.datagrid.mass-delete-failed'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.association_types.index.datagrid.mass-delete-success'),
        ]);
    }

    /**
     * Updates the status of association types.
     */
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $associationTypeIds = $massUpdateRequest->input('indices');

        $value = $massUpdateRequest->input('value');

        foreach ($associationTypeIds as $associationTypeId) {
            try {
                Event::dispatch('catalog.association_type.update.before', $associationTypeId);

                $this->associationTypeRepository->update([
                    'status' => $value,
                ], $associationTypeId);

                Event::dispatch('catalog.association_type.update.after', $associationTypeId);
            } catch (\Exception $e) {
                report($e);

                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.association_types.index.datagrid.mass-update-success'),
        ], JsonResponse::HTTP_OK);
    }
}

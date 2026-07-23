<?php

namespace Webkul\Resource\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\Resource\Contracts\ResourceInterface;

abstract class AbstractResourceController extends Controller
{
    /**
     * The resource definition this controller delegates CRUD to.
     */
    abstract protected function resource(): ResourceInterface;

    /**
     * Grid page (or datagrid JSON on ajax).
     */
    public function index(): View|JsonResponse
    {
        $resource = $this->resource();

        if (request()->ajax()) {
            return resolve($resource->dataGrid())->toJson();
        }

        return view('resource::index', [
            'resource'    => $resource->toViewModel(),
            'datagridSrc' => route($resource->routePrefix().'.index'),
        ]);
    }

    /**
     * Create page.
     */
    public function create(): View
    {
        return view('resource::edit', [
            'resource' => $this->resource()->toViewModel(),
            'record'   => null,
            'mode'     => 'create',
        ]);
    }

    /**
     * Edit page.
     */
    public function edit(int $id): View
    {
        $resource = $this->resource();

        $record = resolve($resource->repository())->findOrFail($id);

        return view('resource::edit', [
            'resource' => $resource->toViewModel(),
            'record'   => collect($record->toArray())
                ->only(array_merge(['id'], $this->schemaFieldNames($resource)))
                ->all(),
            'mode' => 'edit',
        ]);
    }

    /**
     * Store a new record via the resource's repository.
     */
    public function store(): JsonResponse
    {
        $resource = $this->resource();

        // Resolving the FormRequest still runs validation (throws 422 on failure).
        $request = resolve($resource->request());

        $record = resolve($resource->repository())->create($request->only($this->schemaFieldNames($resource)));

        session()->flash('success', trans('resource::app.create-success'));

        return new JsonResponse([
            'data'    => ['redirect_url' => route($resource->routePrefix().'.edit', $record->id)],
            'message' => trans('resource::app.create-success'),
        ]);
    }

    /**
     * Update an existing record via the resource's repository.
     */
    public function update(int $id): JsonResponse
    {
        $resource = $this->resource();

        // Resolving the FormRequest still runs validation (throws 422 on failure).
        $request = resolve($resource->request());

        resolve($resource->repository())->update($request->only($this->schemaFieldNames($resource)), $id);

        session()->flash('success', trans('resource::app.update-success'));

        return new JsonResponse([
            'data'    => ['redirect_url' => route($resource->routePrefix().'.edit', $id)],
            'message' => trans('resource::app.update-success'),
        ]);
    }

    /**
     * Delete a record via the resource's repository.
     */
    public function destroy(int $id): JsonResponse
    {
        $repository = resolve($this->resource()->repository());

        if (! $repository->find($id)) {
            return new JsonResponse(['message' => trans('resource::app.not-found')], 404);
        }

        $repository->delete($id);

        return new JsonResponse(['message' => trans('resource::app.delete-success')]);
    }

    /**
     * All field names declared by the resource's schema.
     *
     * Used to whitelist persisted/exposed data instead of relying on the
     * FormRequest's validated() subset, which silently drops schema fields
     * that don't carry a validation rule (see FieldSchema::rules()).
     */
    private function schemaFieldNames(ResourceInterface $resource): array
    {
        return array_column($resource->schema()->toArray(), 'name');
    }
}

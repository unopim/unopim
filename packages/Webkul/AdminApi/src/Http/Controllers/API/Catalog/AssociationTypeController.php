<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\AssociationTypeDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Catalog\StoreAssociationTypeFieldRequest;
use Webkul\AdminApi\Http\Requests\Catalog\StoreAssociationTypeRequest;
use Webkul\AdminApi\Http\Requests\Catalog\UpdateAssociationTypeFieldRequest;
use Webkul\AdminApi\Http\Requests\Catalog\UpdateAssociationTypeRequest;
use Webkul\Product\Contracts\AssociationTypeField;
use Webkul\Product\Repositories\AssociationTypeFieldRepository;
use Webkul\Product\Repositories\AssociationTypeRepository;

class AssociationTypeController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AssociationTypeRepository $associationTypeRepository,
        protected AssociationTypeFieldRepository $associationTypeFieldRepository
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(AssociationTypeDataSource::class)->toJson();
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Display a single result of the resource.
     */
    public function get(string $code): JsonResponse
    {
        try {
            return response()->json(app(AssociationTypeDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAssociationTypeRequest $request): JsonResponse
    {
        $requestData = $this->setLabels($request->all());

        $denied = $this->deniedChildWrites(
            $requestData['fields'] ?? null,
            'api.catalog.association_types.fields',
            'fields',
            fn (): bool => true,
        ) ?? $this->invalidFieldEntries($requestData['fields'] ?? null, new Collection);

        if ($denied) {
            return $denied;
        }

        $requestData['status'] = $requestData['status'] ?? true;
        $requestData['is_user_defined'] = 1;
        $requestData['position'] = ((int) $this->associationTypeRepository->max('position')) + 1;

        try {
            Event::dispatch('catalog.association_type.create.before');

            $associationType = $this->associationTypeRepository->create($requestData);

            Event::dispatch('catalog.association_type.create.after', $associationType);

            return $this->successResponse(
                trans('admin::app.catalog.association_types.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssociationTypeRequest $request, string $code): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.not-found', ['code' => $code]));
        }

        $immutable = array_intersect(['code', 'is_user_defined'], array_keys(request()->all()));

        if (! empty($immutable)) {
            return $this->validateErrorResponse([
                'immutable' => [trans('admin::app.catalog.association_types.immutable-fields', ['fields' => implode(', ', $immutable)])],
            ]);
        }

        $requestData = $this->setLabels(request()->except(['code', 'is_user_defined']));

        $storedFields = $associationType->fields()->get(['id', 'code', 'type'])->keyBy(fn (AssociationTypeField $field): string => (string) $field->id);

        $existingFields = $storedFields->mapWithKeys(fn (AssociationTypeField $field): array => [(string) $field->code => (string) $field->id]);

        $denied = $this->deniedChildWrites(
            $requestData['fields'] ?? null,
            'api.catalog.association_types.fields',
            'fields',
            fn (array $field, int|string $fieldId): bool => $this->resolveExistingField($existingFields, $field, $fieldId) === null,
            gateEdits: true,
        ) ?? $this->invalidFieldEntries($requestData['fields'] ?? null, $storedFields, $existingFields);

        if ($denied) {
            return $denied;
        }

        try {
            Event::dispatch('catalog.association_type.update.before');

            $associationType = $this->associationTypeRepository->update($requestData, $associationType->id);

            Event::dispatch('catalog.association_type.update.after', $associationType);

            return $this->successResponse(
                trans('admin::app.catalog.association_types.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Validate the fields of a parent store or update with the rules of the field routes.
     *
     * A new field must pass the field store rules, and an existing one the field update rules,
     * with its code and type left as stored.
     *
     * @param  Collection<string, AssociationTypeField>  $storedFields  existing fields keyed by id
     * @param  Collection<string, string>|null  $existingFields  existing field ids keyed by code
     */
    private function invalidFieldEntries(mixed $entries, Collection $storedFields, ?Collection $existingFields = null): ?JsonResponse
    {
        foreach (is_array($entries) ? $entries : [] as $fieldId => $field) {
            if (! is_array($field)) {
                return $this->validateErrorResponse(['fields' => [trans('validation.array', ['attribute' => 'fields'])]]);
            }

            if (filter_var($field['isDelete'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                continue;
            }

            $stored = $storedFields->get((string) $this->resolveExistingField($existingFields ?? new Collection, $field, $fieldId));

            if (! $stored) {
                $validator = Validator::make($field, StoreAssociationTypeFieldRequest::fieldRules());

                if ($validator->fails()) {
                    return $this->validateErrorResponse($validator);
                }

                continue;
            }

            $changed = array_keys(array_filter(
                ['code' => $field['code'] ?? $stored->code, 'type' => $field['type'] ?? $stored->type],
                fn (mixed $value, string $key): bool => (string) $value !== (string) $stored->{$key},
                ARRAY_FILTER_USE_BOTH,
            ));

            if ($changed !== []) {
                return $this->validateErrorResponse([
                    'immutable' => [trans('admin::app.catalog.association_types.immutable-fields', ['fields' => implode(', ', $changed)])],
                ]);
            }

            $validator = Validator::make($field, UpdateAssociationTypeFieldRequest::fieldRules());

            if ($validator->fails()) {
                return $this->validateErrorResponse($validator);
            }
        }

        return null;
    }

    /**
     * The existing field id an update entry writes to, resolved the way AssociationTypeRepository::update() does.
     *
     * @param  Collection<string, string>  $existingFields  field ids keyed by field code
     * @param  array<string, mixed>  $field
     */
    private function resolveExistingField(Collection $existingFields, array $field, int|string $fieldId): ?string
    {
        $byCode = $existingFields->get((string) ($field['code'] ?? ''));

        if (filter_var($field['isNew'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return $byCode;
        }

        return $existingFields->contains((string) $fieldId) ? (string) $fieldId : $byCode;
    }

    /**
     * Partially update the specified resource.
     */
    public function partialUpdate(UpdateAssociationTypeRequest $request, string $code): JsonResponse
    {
        return $this->update($request, $code);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $code): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.not-found', ['code' => $code]));
        }

        if (! $associationType->is_user_defined) {
            return $this->validateErrorResponse(
                ['code' => [trans('admin::app.catalog.association_types.user-define-error')]],
                trans('admin::app.catalog.association_types.user-define-error')
            );
        }

        try {
            Event::dispatch('catalog.association_type.delete.before', $associationType->id);

            $this->associationTypeRepository->delete($associationType->id);

            Event::dispatch('catalog.association_type.delete.after', $associationType->id);

            return $this->successResponse(trans('admin::app.catalog.association_types.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Display the custom fields defined on the association type.
     */
    public function getFields(string $code): JsonResponse
    {
        try {
            return response()->json(app(AssociationTypeDataSource::class)->getFieldsByTypeCode($code));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly created field on the association type.
     */
    public function storeField(StoreAssociationTypeFieldRequest $request, string $code): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.not-found', ['code' => $code]));
        }

        $requestData = $this->setLabels($request->all());
        $requestData['status'] = $requestData['status'] ?? true;
        $requestData['is_required'] = $requestData['is_required'] ?? false;
        $requestData['is_unique'] = $requestData['is_unique'] ?? false;
        $requestData['value_per_locale'] = $requestData['value_per_locale'] ?? false;
        $requestData['validation'] = $requestData['validation'] ?? null;
        $requestData['association_type_id'] = $associationType->id;

        try {
            $this->associationTypeFieldRepository->create($requestData);

            return $this->successResponse(
                trans('admin::app.catalog.association_types.fields.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Update the specified field on the association type.
     */
    public function updateField(UpdateAssociationTypeFieldRequest $request, string $code, string $fieldCode): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.not-found', ['code' => $code]));
        }

        $field = $this->associationTypeFieldRepository->findOneWhere([
            'code'                => $fieldCode,
            'association_type_id' => $associationType->id,
        ]);

        if (! $field) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.fields.not-found', ['code' => $fieldCode]));
        }

        $immutable = array_intersect(['code', 'type'], array_keys(request()->all()));

        if (! empty($immutable)) {
            return $this->validateErrorResponse([
                'immutable' => [trans('admin::app.catalog.association_types.immutable-fields', ['fields' => implode(', ', $immutable)])],
            ]);
        }

        $requestData = $this->setLabels(request()->except(['code', 'type', 'association_type_id']));

        try {
            $this->associationTypeFieldRepository->update($requestData, $field->id);

            return $this->successResponse(
                trans('admin::app.catalog.association_types.fields.update-success'),
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Remove the specified field from the association type.
     */
    public function deleteField(string $code, string $fieldCode): JsonResponse
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.not-found', ['code' => $code]));
        }

        $field = $this->associationTypeFieldRepository->findOneWhere([
            'code'                => $fieldCode,
            'association_type_id' => $associationType->id,
        ]);

        if (! $field) {
            return $this->modelNotFoundResponse(trans('admin::app.catalog.association_types.fields.not-found', ['code' => $fieldCode]));
        }

        try {
            $this->associationTypeFieldRepository->delete($field->id);

            return $this->successResponse(trans('admin::app.catalog.association_types.fields.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }
}

<?php

declare(strict_types=1);

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\Catalog\VariantStructureDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Catalog\StoreVariantStructureRequest;
use Webkul\AdminApi\Http\Requests\Catalog\UpdateVariantStructureRequest;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Repositories\VariantStructureRepository;
use Webkul\Product\Services\VariantStructureWriter;

/**
 * Reads and maintains the variant structures of an attribute family.
 *
 * Covers the whole lifecycle: a structure may be created here or in the Admin UI,
 * and either way this controller reads, updates and removes it.
 */
class VariantStructureController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected VariantStructureRepository $variantStructureRepository,
        protected VariantStructureWriter $variantStructureWriter,
    ) {}

    /**
     * Display the variant structures belonging to the family.
     */
    public function index(string $code): JsonResponse
    {
        try {
            return app(VariantStructureDataSource::class)
                ->forFamily($this->findFamilyOrFail($code))
                ->toJson();
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Display a single variant structure of the family.
     */
    public function get(string $code, string $structureCode): JsonResponse
    {
        try {
            $attributeFamily = $this->findFamilyOrFail($code);

            return response()->json(
                $this->present($attributeFamily, $this->findStructureOrFail($attributeFamily, $structureCode))
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Store a newly created variant structure for the family.
     *
     * The whole shape is stated once here — `levels` and `axes` included, since this
     * is the only verb that may set them — and the writer decides whether it is legal
     * for the family. The created structure comes back in the shape {@see get()} serves.
     */
    public function store(StoreVariantStructureRequest $request, string $code): JsonResponse
    {
        try {
            $attributeFamily = $this->findFamilyOrFail($code);

            $structure = $this->variantStructureWriter->create($attributeFamily, $request->validated());

            return $this->successResponse(
                trans('admin::app.catalog.families.edit.variant-saved'),
                Response::HTTP_CREATED,
                $this->present($attributeFamily, $structure)
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Replace the specified variant structure.
     *
     * Only `name` and `placements` are writable; `levels` and `axes` are fixed at
     * creation and always come from storage. A PUT states them in full: an omitted
     * `placements` clears them, an omitted `name` falls back to the code.
     */
    public function update(UpdateVariantStructureRequest $request, string $code, string $structureCode): JsonResponse
    {
        return $this->write($request, $code, $structureCode, false);
    }

    /**
     * Partially update the specified variant structure.
     *
     * Identical to {@see update()} except that an omitted `name` or
     * `placements` is inherited from the stored structure instead of reset.
     */
    public function partialUpdate(UpdateVariantStructureRequest $request, string $code, string $structureCode): JsonResponse
    {
        return $this->write($request, $code, $structureCode, true);
    }

    /**
     * Remove the specified variant structure from storage.
     */
    public function delete(string $code, string $structureCode): JsonResponse
    {
        try {
            $attributeFamily = $this->findFamilyOrFail($code);

            $this->variantStructureWriter->delete($this->findStructureOrFail($attributeFamily, $structureCode));

            return $this->successResponse(trans('admin::app.catalog.families.edit.variant-structure-deleted'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Resolve the desired state from the request and hand it to the writer.
     */
    protected function write(UpdateVariantStructureRequest $request, string $code, string $structureCode, bool $partial): JsonResponse
    {
        try {
            $attributeFamily = $this->findFamilyOrFail($code);
            $structure = $this->findStructureOrFail($attributeFamily, $structureCode);
            $payload = $request->validated();

            $this->variantStructureWriter->assertImmutableFieldsUnchanged($structure, $payload);

            $structure = $this->variantStructureWriter->save(
                $attributeFamily,
                $structure,
                $this->desiredState($payload, $structure, $partial)
            );

            return $this->successResponse(
                trans('admin::app.catalog.families.edit.variant-saved'),
                Response::HTTP_OK,
                $this->present($attributeFamily, $structure)
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Merge the validated payload over the stored state.
     *
     * `levels` and `axes` always come from storage, echoed back or not; a differing
     * echo is rejected by the writer. The verbs differ only in the writable pair:
     * PATCH inherits an omission, PUT resets it.
     *
     * @return array<string, mixed>
     */
    protected function desiredState(array $payload, VariantStructure $structure, bool $partial): array
    {
        $current = $this->variantStructureWriter->currentState($structure);

        if (! $partial) {
            return [
                'name'       => $payload['name'] ?? null,
                'levels'     => $current['levels'],
                'axes'       => $current['axes'],
                'placements' => $payload['placements'] ?? [],
            ];
        }

        return [
            'name'       => array_key_exists('name', $payload) ? $payload['name'] : $current['name'],
            'levels'     => $current['levels'],
            'axes'       => $current['axes'],
            'placements' => $payload['placements'] ?? $current['placements'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(AttributeFamily $attributeFamily, VariantStructure $structure): array
    {
        return app(VariantStructureDataSource::class)
            ->forFamily($attributeFamily)
            ->normalize($structure);
    }

    /**
     * @throws ModelNotFoundException When no family carries the code.
     */
    protected function findFamilyOrFail(string $code): AttributeFamily
    {
        $attributeFamily = $this->attributeFamilyRepository->findOneByField('code', $code);

        if (! $attributeFamily) {
            throw new ModelNotFoundException(
                trans('admin::app.catalog.families.not-found', ['code' => $code])
            );
        }

        return $attributeFamily;
    }

    /**
     * @throws ModelNotFoundException When the family carries no such structure.
     */
    protected function findStructureOrFail(AttributeFamily $attributeFamily, string $structureCode): VariantStructure
    {
        $structure = $this->variantStructureRepository->findByFamilyAndCode($attributeFamily->id, $structureCode);

        if (! $structure) {
            throw new ModelNotFoundException(trans('admin::app.catalog.products.variant-structure-not-found', [
                'code'   => $structureCode,
                'family' => $attributeFamily->code,
            ]));
        }

        return $structure;
    }
}

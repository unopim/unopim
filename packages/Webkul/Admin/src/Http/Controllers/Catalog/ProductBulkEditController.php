<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\BulkEditRequest;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\DataTransfer\Jobs\System\BulkProductUpdate;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Validator\API\UploadMediaValidator;

class ProductBulkEditController extends Controller
{
    /**
     * Default number of items per page for pagination.
     *
     * @var int
     */
    const DEFAULT_PER_PAGE = 20;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected JobInstancesRepository $jobInstancesRepository,
        protected JobTrackRepository $jobTrackRepository,
        protected ProductRepository $productRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected UploadMediaValidator $mediaValidator,
        protected FileStorer $fileStorer,
        protected VariantStructurePlanner $variantStructurePlanner
    ) {}

    /**
     * Apply filters for bulk edit and store filtered product & attribute IDs in session.
     */
    public function filters(BulkEditRequest $bulkEditRequest): JsonResponse
    {
        $productIds = $bulkEditRequest->input('indices', []);
        $filters = $bulkEditRequest->input('filter', []);

        if (count($productIds) > 100) {
            return response()->json([
                'message' => trans('admin::app.catalog.products.bulk-edit.filter.many-product'),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        session(['bulk_edit_product_ids' => $productIds]);

        $attributeIds = [];

        if (! empty($filters['filtered_attributes'])) {
            $attributeIds = collect($filters['filtered_attributes'])->pluck('id')->all();
        }

        session(['bulk_edit_attribute_ids' => $attributeIds]);

        return response()->json([
            'message'  => trans('admin::app.catalog.products.bulk-edit.filter.redirect-message'),
            'redirect' => route('admin.catalog.products.bulkedit'),
        ]);
    }

    /**
     * Show the bulk edit page with filtered products and attributes. Columns follow
     * the order the attributes were selected in, with sku first: a whereIn carries no
     * ordering of its own, so the rows come back in whatever order the table holds.
     */
    public function index(): View|RedirectResponse
    {
        $productIds = session('bulk_edit_product_ids', []);
        $attributeIds = session('bulk_edit_attribute_ids');

        if (empty($productIds)) {
            return redirect()->back()->with('error', trans('admin::app.catalog.products.bulk-edit.index.no-product'));
        }

        $sku = $this->attributeRepository->findWhere(['code' => 'sku'])->first();

        if ($sku && ! in_array($sku->id, $attributeIds)) {
            array_unshift($attributeIds, $sku->id);
        }

        $columns = $this->attributeRepository
            ->whereIn('id', $attributeIds)
            ->with('translations')
            ->get()
            ->sortBy(fn ($attribute) => array_search($attribute->id, $attributeIds, true))
            ->values()
            ->toArray();

        $products = $this->productRepository
            ->with('parent.parent')
            ->findWhereIn('id', $productIds);

        $this->variantStructurePlanner->primeStructuresFor($products);

        $attributeCodes = array_column($columns, 'code');

        $rows = $products
            ->map(fn (Product $product) => $this->buildBulkEditRow($product, $attributeCodes))
            ->all();

        return view('admin::catalog.bulk-edit.index', compact('columns', 'rows'));
    }

    /**
     * Own values, values inherited from real ancestors, and each code's state here:
     * "own" (editable), "inherited" (locked, shows the owner's value) or "na"
     * (owned below this level). Owners come from {@see VariantStructurePlanner::ownsAtOwnLevel()},
     * not a placement lookup, because axes carry no placement row.
     */
    protected function buildBulkEditRow(Product $product, array $attributeCodes): array
    {
        $structure = $this->variantStructurePlanner->structureFor($product);

        $ancestors = $this->ancestorsOf($product);

        $locks = [];
        $inheritedValues = [
            'common'                  => [],
            'channel_specific'        => [],
            'locale_specific'         => [],
            'channel_locale_specific' => [],
        ];

        foreach ($attributeCodes as $code) {
            if ($code === 'sku' || $this->variantStructurePlanner->ownsAtOwnLevel($product, $code)) {
                $locks[$code] = 'own';

                continue;
            }

            if (! $this->variantStructurePlanner->ownsAttribute($product, $code)) {
                $locks[$code] = 'na';

                continue;
            }

            $locks[$code] = 'inherited';

            foreach ($ancestors as $ancestor) {
                if ($this->variantStructurePlanner->ownsAtOwnLevel($ancestor, $code)) {
                    $this->copyAttributeValue($ancestor->values ?? [], $inheritedValues, $code);

                    break;
                }
            }
        }

        $ownAxisCodes = $structure
            ? array_values(array_filter(
                $this->variantStructurePlanner->allAxisCodes($structure),
                fn (string $code): bool => $this->variantStructurePlanner->ownsAtOwnLevel($product, $code)
            ))
            : [];

        return [
            'id'                   => $product->id,
            'type'                 => $product->type,
            'parent_id'            => $product->parent_id,
            'variant_structure_id' => $product->variant_structure_id,
            'values'               => $product->values,
            'inheritedValues'      => $inheritedValues,
            'locks'                => $locks,
            'axes'                 => $ownAxisCodes,
        ];
    }

    /**
     * A product's real ancestors, nearest first. The same chain
     * {@see VariantValueResolver::resolve()} merges over, walked with the same
     * depth guard so a cycle in parent_id cannot spin here either.
     *
     * @return array<int, Product>
     */
    protected function ancestorsOf(Product $product): array
    {
        $ancestors = [];
        $node = $product->parent;
        $guard = 0;

        while ($node && $guard++ < 10) {
            $ancestors[] = $node;
            $node = $node->parent;
        }

        return $ancestors;
    }

    /**
     * Copy one attribute's value, across every scope bucket it appears in,
     * from an owner's raw values into the row's inherited-values scaffold.
     */
    protected function copyAttributeValue(array $ownerValues, array &$inheritedValues, string $code): void
    {
        if (array_key_exists($code, $ownerValues['common'] ?? [])) {
            $inheritedValues['common'][$code] = $ownerValues['common'][$code];
        }

        foreach ($ownerValues['channel_specific'] ?? [] as $channel => $bucket) {
            if (array_key_exists($code, $bucket)) {
                $inheritedValues['channel_specific'][$channel][$code] = $bucket[$code];
            }
        }

        foreach ($ownerValues['locale_specific'] ?? [] as $locale => $bucket) {
            if (array_key_exists($code, $bucket)) {
                $inheritedValues['locale_specific'][$locale][$code] = $bucket[$code];
            }
        }

        foreach ($ownerValues['channel_locale_specific'] ?? [] as $channel => $locales) {
            foreach ($locales as $locale => $bucket) {
                if (array_key_exists($code, $bucket)) {
                    $inheritedValues['channel_locale_specific'][$channel][$locale][$code] = $bucket[$code];
                }
            }
        }
    }

    /**
     * Store uploaded product media for a given attribute.
     */
    public function storeProductMedia(): JsonResponse
    {
        request()->validate([
            'sku'       => 'required|string',
            'file'      => 'required',
            'attribute' => 'required|string',
        ]);

        $requestData = request()->all();

        try {
            $product = $this->productRepository->findOrFail($requestData['sku']);
            $productId = $product->id;

            $this->mediaValidator->validate($requestData, $productId);
        } catch (ValidationException|ModelNotFoundException $e) {
            if ($e instanceof ModelNotFoundException) {
                report($e);

                return new JsonResponse(['message' => trans('admin::app.catalog.products.bulk-edit.img-fail')], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->validateErrorResponse($e->validator->errors()->messages());
        }

        $uploadedFiles = request()->file('file');

        // Normalize to array
        $uploadedFiles = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];
        $attribute = $requestData['attribute'];
        $filePath = [];

        try {
            foreach ($uploadedFiles as $file) {
                if ($file instanceof UploadedFile) {
                    $filePath[] = $this->fileStorer->store(
                        path: 'product'.DIRECTORY_SEPARATOR.$productId.DIRECTORY_SEPARATOR.$attribute,
                        file: $file
                    );
                }
            }

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'filePath' => implode(',', $filePath),
                ],
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse(['message' => trans('admin::app.catalog.products.bulk-edit.img-fail')], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Return a formatted JSON response for validation errors.
     */
    protected function validateErrorResponse(mixed $validator, string $message = 'Validation failed.', int $code = JsonResponse::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        $errors = $validator instanceof Validator ? (new ValidationException($validator))->errors() : $validator;

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    /**
     * Handle bulk save of product updates via queued job.
     */
    public function handleBulkSave(): JsonResponse
    {
        $data = request()->all();

        $this->validate(request(), [
            'data' => 'required',
        ]);

        $errors = $this->validateNumericAttributeValues($data['data'] ?? []);

        if (empty($errors)) {
            $errors = $this->validateVariantLevelValues($data['data'] ?? []);
        }

        if (! empty($errors)) {
            return response()->json([
                'message' => trans('admin::app.catalog.products.bulk-edit.validation.failed'),
                'errors'  => $errors,
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $jobInstance = $this->jobInstancesRepository->find(['code' => 'bulk_product_update']);

        if (! $jobInstance) {
            $jobInstance = $this->createBulkProductJobInstance();
        }

        $userId = auth()->guard('admin')->user()->id;

        BulkProductUpdate::dispatch($data['data'], $userId);

        session()->forget('bulk_edit_product_ids');

        return response()->json([
            'message'      => trans('admin::app.catalog.products.bulk-edit.handle-save.edit-success'),
            'redirect'     => route('admin.catalog.products.index'),
            'status'       => 'success',
            'redirect_url' => route('admin.catalog.products.index'),
        ]);
    }

    /**
     * Flatten the bulk-edit payload and return ["<attribute_code>" => [messages]]
     * for attributes whose numeric types (price, integer, decimal) got non-numeric values.
     */
    protected function validateNumericAttributeValues(array $data): array
    {
        $attributeCodes = [];

        foreach ($data as $perProduct) {
            if (! is_array($perProduct)) {
                continue;
            }

            foreach (array_keys($perProduct) as $code) {
                $attributeCodes[$code] = true;
            }
        }

        if (empty($attributeCodes)) {
            return [];
        }

        $numericTypes = ['price', 'integer', 'decimal'];

        $numericCodes = $this->attributeRepository
            ->whereIn('code', array_keys($attributeCodes))
            ->whereIn('type', $numericTypes)
            ->pluck('type', 'code');

        if ($numericCodes->isEmpty()) {
            return [];
        }

        $errors = [];

        foreach ($data as $perProduct) {
            if (! is_array($perProduct)) {
                continue;
            }

            foreach ($perProduct as $code => $value) {
                if (! $numericCodes->has($code)) {
                    continue;
                }

                foreach ($this->flattenScalarValues($value) as $scalar) {
                    if ($scalar === '' || $scalar === null) {
                        continue;
                    }

                    if (! is_numeric($scalar)) {
                        $errors[$code][] = trans('admin::app.catalog.products.bulk-edit.validation.numeric', ['attribute' => $code]);
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Pre-flight variant-level check so a forbidden write is reported in this request
     * rather than as a job-tracker warning. Runs {@see ProductRepository::guardVariantLevelWrite()}
     * with no persist closure, so it writes nothing. Fast feedback only —
     * {@see BulkProductUpdate} still guards at write time and must keep doing so.
     *
     * @param  array<int|string, mixed>  $data  Bulk-edit payload, keyed by product id.
     * @return array<string, array<int, string>>
     */
    protected function validateVariantLevelValues(array $data): array
    {
        $productIds = array_filter(array_keys($data), 'is_numeric');

        if ($productIds === []) {
            return [];
        }

        $attributeCodes = [];

        foreach ($data as $perProduct) {
            if (is_array($perProduct)) {
                $attributeCodes += array_flip(array_keys($perProduct));
            }
        }

        unset($attributeCodes['sku']);

        if ($attributeCodes === []) {
            return [];
        }

        $attributes = $this->attributeRepository
            ->whereIn('code', array_keys($attributeCodes))
            ->get()
            ->keyBy('code');

        $products = $this->productRepository
            ->with('parent.parent')
            ->findWhereIn('id', $productIds)
            ->filter(fn (Product $product): bool => (bool) $product->parent_id);

        $this->variantStructurePlanner->primeStructuresFor($products);

        $errors = [];

        foreach ($products as $product) {
            $submittedCommon = $this->submittedCommonValues($data[$product->id] ?? [], $attributes);

            if ($submittedCommon === []) {
                continue;
            }

            try {
                $this->productRepository->guardVariantLevelWrite(
                    $product,
                    $submittedCommon,
                    null,
                    $this->variantStructurePlanner
                );
            } catch (ValidationException $e) {
                foreach ($e->errors() as $key => $messages) {
                    foreach ($messages as $message) {
                        $errors[$key][] = $product->sku.': '.$message;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * The scope-less values from one product's bulk-edit row, the shape
     * {@see ProductRepository::guardVariantLevelWrite()} expects. Channel- and
     * locale-scoped attributes are dropped because their payload is bucketed by
     * scope; what a product may write is left to the guard.
     *
     * @param  mixed  $row  One product's submitted attribute code => value pairs.
     * @param  Collection<string, Attribute>  $attributes  Submitted attributes, keyed by code.
     * @return array<string, mixed>
     */
    protected function submittedCommonValues(mixed $row, Collection $attributes): array
    {
        if (! is_array($row)) {
            return [];
        }

        $common = [];

        foreach ($row as $code => $value) {
            $attribute = $attributes->get($code);

            if (! $attribute || $attribute->isLocaleBasedAttribute() || $attribute->isChannelBasedAttribute()) {
                continue;
            }

            $common[$code] = $value;
        }

        return $common;
    }

    /**
     * Yield every scalar leaf from an arbitrarily-nested array.
     */
    protected function flattenScalarValues(mixed $value): \Generator
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                yield from $this->flattenScalarValues($v);
            }
        } else {
            yield $value;
        }
    }

    /**
     * Create a new job instance for bulk product update.
     *
     * @return mixed
     */
    public function createBulkProductJobInstance()
    {
        $job = $this->jobInstancesRepository->create([
            'type'                => 'system',
            'action'              => 'update',
            'code'                => 'bulk_product_update',
            'entity_type'         => 'products',
            'validation_strategy' => 'strict',
        ]);

        return $job;
    }

    /**
     * Retrieve attributes for bulk edit. Axis attributes are offered like any other
     * because {@see ProductRepository::guardVariantLevelWrite()} accepts an axis write
     * on the node owning it and rejects only a sibling tuple collision. The family
     * filter is skipped when it resolves nothing, so a stale session of deleted
     * product ids leaves the picker unfiltered rather than empty.
     */
    public function getAttributes(Request $request): JsonResponse
    {
        $query = $this->attributeRepository
            ->whereNotIn('code', ['sku'])
            ->whereNotIn('type', ['table']);

        $productIds = session('bulk_edit_product_ids', []);

        if (! empty($productIds)) {
            $familyAttributeIds = DB::table('attributes as a')
                ->distinct()
                ->join('attribute_group_mappings as agm', 'agm.attribute_id', '=', 'a.id')
                ->join('attribute_family_group_mappings as afgm', 'afgm.id', '=', 'agm.attribute_family_group_id')
                ->join('products as p', 'p.attribute_family_id', '=', 'afgm.attribute_family_id')
                ->whereIn('p.id', $productIds)
                ->pluck('a.id')
                ->toArray();

            if ($familyAttributeIds !== []) {
                $query = $query->whereIn('id', $familyAttributeIds);
            }
        }

        if ($request->filled('ids')) {
            $ids = (array) $request->input('ids');
            $attributes = $query->whereIn('id', $ids)->paginate(self::DEFAULT_PER_PAGE);

        } elseif ($request->filled('query')) {
            $queryParam = $request->input('query', '');

            $attributes = $query->where(function ($queryBuilder) use ($queryParam) {
                $queryBuilder->whereTranslationLike('name', '%'.$queryParam.'%')
                    ->orWhere('code', 'like', '%'.$queryParam.'%');
            })->paginate(self::DEFAULT_PER_PAGE);

        } else {
            $attributes = $query->orderBy('id', 'asc')->paginate(self::DEFAULT_PER_PAGE);
        }

        $currentLocaleCode = core()->getRequestedLocaleCode();

        $formattedAttributes = [];

        foreach ($attributes as $attribute) {
            $translatedLabel = $attribute->translate($currentLocaleCode);

            $formattedAttributes[] = [
                'id'    => $attribute->id,
                'code'  => $attribute->code,
                'name'  => ! empty($translatedLabel->name) ? $translatedLabel->name : "[{$attribute->code}]",
                ...$attribute->makeHidden(['translations', 'name'])->toArray(),
            ];
        }

        return new JsonResponse([
            'options'  => $formattedAttributes,
            'page'     => $attributes->currentPage(),
            'lastPage' => $attributes->lastPage(),
        ]);
    }
}

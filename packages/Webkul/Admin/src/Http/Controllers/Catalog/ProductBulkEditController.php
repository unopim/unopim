<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\BulkEditRequest;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\DataTransfer\Jobs\System\BulkProductUpdate;
use Webkul\DataTransfer\Repositories\JobInstancesRepository;
use Webkul\DataTransfer\Repositories\JobTrackRepository;
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
        protected FileStorer $fileStorer
    ) {}

    /**
     * Apply filters for bulk edit and store filtered product & attribute IDs in session.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function filters(BulkEditRequest $bulkEditRequest)
    {
        $productIds = $bulkEditRequest->input('indices', []);
        $filters = $bulkEditRequest->input('filter', []);

        if (count($productIds) > 100) {
            return response()->json([
                'message' => trans('admin::app.catalog.products.bulk-edit.filter.many-product'),
            ], 422);
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
     * Show the bulk edit page with filtered products and attributes.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index()
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

        $columns = $this->attributeRepository->whereIn('id', $attributeIds)->with('translations')->get()->toArray();

        $rows = $this->productRepository
            ->select('id', 'values')
            ->whereIn('id', $productIds)
            ->get()->toArray();

        return view('admin::catalog.bulk-edit.index', compact('columns', 'rows'));
    }

    /**
     * Store uploaded product media for a given attribute.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeProductMedia()
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validateErrorResponse(mixed $validator, string $message = 'Validation failed.', int $code = 422)
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
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleBulkSave()
    {
        $data = request()->all();

        $this->validate(request(), [
            'data' => 'required',
        ]);

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
     * Create a new job instance for bulk product update.
     *
     * @return mixed
     */
    public function createBulkProductJobInstance()
    {
        $job = $this->jobInstancesRepository->create([
            'type'           => 'system',
            'action'         => 'update',
            'code'           => 'bulk_product_update',
            'entity_type'    => 'products',
        ]);

        return $job;
    }

    /**
     * Retrieve attributes for bulk edit.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttributes(Request $request)
    {
        $query = $this->attributeRepository
            ->whereNotIn('code', ['sku'])
            ->whereNotIn('type', ['table', 'file']);

        if ($request->filled('ids')) {
            $ids = (array) $request->input('ids');
            $attributes = $query->whereIn('id', $ids)->paginate(self::DEFAULT_PER_PAGE);

        } elseif ($request->filled('query')) {
            $queryParam = $request->get('query', '');

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

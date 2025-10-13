<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Columns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Models\AttributeColumnOption;
use Webkul\Attribute\Repositories\AttributeColumnOptionRepository;
use Webkul\Attribute\Repositories\AttributeColumnRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Rules\Code;

class TableAttributeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeColumnRepository $attributeColumnRepository,
        protected AttributeColumnOptionRepository $attributeColumnOptionRepository
    ) {}

    /**
     * Adds a new column to a table attribute, creating options if the column type is 'select'.
     *
     * @param  int  $attributeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addColumn(Request $request, $attributeId)
    {
        $attribute = $this->attributeRepository->where('type', 'table')->findOrFail($attributeId);

        $rules = [
            'code' => [
                'required',
                'string',
                Rule::unique('attribute_columns')->where(function ($query) use ($attributeId) {
                    return $query->where('attribute_id', $attributeId);
                }),
                new Code,
            ],
            'type'       => 'required|in:text,select,multiselect,image,date,boolean',
            'validation' => 'nullable|string',
        ];

        $validated = $this->validateWithLocales($request, $rules);

        $this->attributeColumnRepository->create(array_merge($validated, [
            'attribute_id' => $attribute->id,
        ]));

        return response()->json(201);
    }

    /**
     * Retrieve the attribute column for the specified attribute ID.
     *
     * @param  int  $id  The unique identifier of the attribute.
     * @return \Illuminate\Http\Response
     */
    public function getAttributeColumn(int $id)
    {
        $column = $this->attributeColumnRepository->findOrFail($id);

        $data = $column->toArray();

        $options = $column->options()
            ->with('translations')
            ->limit(50)
            ->get()
            ->toArray();

        $data = $column->toArray();
        $data['options'] = $options;

        return new JsonResponse($data);
    }

    /**
     * Updates an existing attribute column.
     *
     * @param  int  $columnId
     * @return JsonResponse
     */
    public function updateColumn(Request $request, $columnId)
    {
        $request->validate([
            'code'       => ['required', new Code],
            'type'       => 'required',
            'validation' => 'nullable|string',
        ]);

        $column = $this->attributeColumnRepository->findOrFail($columnId);

        $data = $request->except(['type', 'code', 'options']);

        $this->attributeColumnRepository->update($data, $columnId);

        return response()->json($column->load('options'));
    }

    /**
     * Retrieve the available column options for a given request.
     *
     * @return JsonResponse
     */
    public function getColumnOptions(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', new Code],
        ]);

        $column = $this->attributeColumnRepository->findOneByField([
            'code' => $validated['code'],
        ]);

        if (! $column) {
            return response()->json([]);
        }

        $options = $column->options()
            ->with('translations')
            ->limit(50)
            ->get()
            ->map(function ($option) {
                return [
                    'code'  => $option->code,
                    'label' => $option->label,
                ];
            });

        return response()->json($options);
    }

    /**
     * Updates an existing option for a column.
     *
     * @param  Request  $request
     * @return void
     */
    public function updateOption(int $id)
    {
        $this->attributeColumnOptionRepository->findOrFail($id);

        $requestData = request()->except(['type', 'code', 'validation', 'options']);

        $this->attributeColumnOptionRepository->update($requestData, $id);
    }

    /**
     * Deletes a column along with its associated options.
     *
     * @param  int  $columnId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteColumn($id)
    {
        try {
            $this->attributeColumnRepository->delete($id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.edit.column.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retrieves options for a specific attribute column with search and pagination.
     *
     * @return JsonResponse
     */
    public function getOptions(Request $request, int $id)
    {
        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 10);
        $search = $request->input('query');

        $query = $this->attributeColumnOptionRepository
            ->with('translations')
            ->where('attribute_column_id', $id);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereTranslationLike('label', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        } else {
            $query->offset($offset);
        }

        $options = $query->limit($limit)->get();

        return response()->json([
            'options' => $options,
        ]);
    }

    /**
     * Adds a new option to a select-type column.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeOption(Request $request, int $columnId)
    {
        $column = $this->attributeColumnRepository->findOrFail($columnId);

        if (! in_array(strtolower($column->type), ['select', 'multiselect'])) {
            throw ValidationException::withMessages([
                'code' => trans('admin::app.catalog.attributes.invalid-column-type'),
            ])->status(422);
        }

        $rules = [
            'code' => [
                'required',
                new Code,
                Rule::unique('attribute_column_options', 'code')
                    ->where('attribute_column_id', $columnId),
            ],
        ];

        $validated = $this->validateWithLocales($request, $rules);

        try {
            $option = $column->options()->create($validated);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse([
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json($option, 201);
    }

    /**
     * Deletes a specific option from a column.
     *
     * @param  int  $optionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteOption(int $id)
    {
        $option = AttributeColumnOption::findOrFail($id);
        $option->delete();

        return response()->json(['message' => trans('admin::app.catalog.attributes.edit.option.delete-success')]);
    }

    /**
     * Validate request with additional locale rules.
     *
     * @param  string  $localeField
     */
    public function validateWithLocales(Request $request, array $rules): array
    {
        foreach (core()->getAllActiveLocales() as $locale) {
            $rules[$locale->code.'.label'] = 'string';
        }

        return $request->validate($rules);
    }
}

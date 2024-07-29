<?php

namespace Webkul\Category\Validator\Catalog;

use Illuminate\Support\Facades\Validator;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Validator\FieldValidator;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Rules\Code;

class CategoryValidator extends FieldValidator
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected ChannelRepository $channelRepository
    ) {}

    /**
     * Validates the category data based on the provided request data and optional category ID.
     *
     * @param  array  $requestData  The request data containing category information.
     * @param  int|null  $id  The optional category ID. If provided, the method will perform additional checks.
     * @return array|\Illuminate\Validation\Validator
     *
     * @example
     * [
     *    'code' => 'root',
     *    'parent' => NULL,
     *    'additional_data' => [
     *       'common' => [
     *         'vendor' => 'Akeneo',
     *       ],
     *       'locale_specific' => [
     *         'de_DE' => [
     *             'name' => 'Name (de_DE)',
     *             'description' => '<p>Description (de_DE)</p>',
     *         ],
     *         'en_US' => [
     *             'name' => 'Name (en_US)',
     *             'description' => '<p>Description (en_US)</p>',
     *         ],
     *         'fr_FR' => [
     *             'name' => 'Name (fr_FR)',
     *             'description' => '<p>Description (fr_FR)</p>',
     *         ],
     *       ],
     *     ],
     *  ]
     */
    public function validate(array $requestData, ?int $id = null)
    {
        if ($id) {
            $isUpdateCategory = $this->isUpdateCategory($requestData, $id);
            if ($isUpdateCategory instanceof \Illuminate\Validation\Validator) {
                return $isUpdateCategory;
            }
        }

        $unknownFieldsValidate = $this->unknownFieldsValidate($requestData);
        if ($unknownFieldsValidate instanceof \Illuminate\Validation\Validator && $unknownFieldsValidate->fails()) {
            return $unknownFieldsValidate;
        }

        $inputFieldsValidate = $this->inputFieldValidate($requestData, $id);
        if ($inputFieldsValidate instanceof \Illuminate\Validation\Validator && $inputFieldsValidate->fails()) {
            return $inputFieldsValidate;
        }

        return [];
    }

    /**
     * Validates whether the category can be updated based on the provided request data and category ID.
     *
     * @param  array  $requestData  The request data containing category information.
     * @param  int  $id  The category ID to be updated.
     * @return array|\Illuminate\Validation\Validator
     */
    protected function isUpdateCategory(array $requestData, int $id)
    {
        if (! empty($requestData['parent_id']) && $this->isRelatedToChannel($id)) {
            $validator = Validator::make([], []);
            $validator->after(function ($validator) {
                $validator->errors()->add('error', trans('admin::app.catalog.categories.can-not-update'));
            });

            return $validator;
        }

        return [];
    }

    /**
     * It checks for unknown fields and returns a validator instance if any unknown fields are found.
     *
     * @param  array  $requestData  The request data containing category information.
     * @return array|\Illuminate\Validation\Validator
     */
    protected function unknownFieldsValidate(array $requestData)
    {
        if (! array_key_exists('additional_data', $requestData)) {
            return [];
        }

        $requestedFields = $this->getRequestFields($requestData);

        $existsFields = $this->getCategoryFields($requestedFields)?->pluck('code')->toArray();

        $unknownFields = array_diff($requestedFields, $existsFields);

        if (! empty($unknownFields)) {
            $validator = Validator::make([], []);
            $validator->after(function ($validator) use ($unknownFields) {
                $validator->errors()->add('additional_data', trans('admin::app.catalog.categories.unknown-fields', ['fields' => implode(', ', $unknownFields)]));
            });

            return $validator;
        }

        return [];
    }

    /**
     * Validates the input fields of the category based on the provided request data and category ID.
     *
     * @param  array  $requestData  The request data containing category information.
     * @param  int|null  $id  The category ID to be validated. If null, it indicates a new category.
     * @return array|\Illuminate\Validation\Validator
     */
    protected function inputFieldValidate(array $requestData, ?int $id)
    {
        if (! array_key_exists('additional_data', $requestData)) {
            return [];
        }

        $existsFields = $this->getCategoryFields();

        $rules = $this->inputFieldsRules($existsFields, $requestData, $id);

        if (! $id) {
            $rules['code'] = ['required', 'unique:categories,code', new Code()];
        }

        return Validator::make($requestData, $rules);
    }

    /**
     * Retrieves the fields present in the request data for categories.
     *
     * @param  array  $requestData  The request data containing category information.
     * @return array An array containing the fields present in the request data.
     */
    protected function getRequestFields(array $requestData): array
    {
        $commonFields = array_key_exists('common', $requestData['additional_data']) ? array_keys($requestData['additional_data']['common']) : [];

        $localeSpecificFields = [];

        if (array_key_exists('locale_specific', $requestData['additional_data'])) {
            foreach ($requestData['additional_data']['locale_specific'] as $details) {
                $localeSpecificFields = array_unique(array_merge($localeSpecificFields, array_keys($details)));
            }
        }

        return array_merge($localeSpecificFields, $commonFields);
    }

    /**
     * Retrieves category fields based on the provided requested fields.
     * If no requested fields are provided, it fetches all active category fields.
     *
     * @param  array  $requestedFields  An optional array containing the codes of requested category fields.
     * @return \Illuminate\Support\Collection A collection of category fields.
     */
    protected function getCategoryFields($requestedFields = [])
    {
        return empty($requestedFields) ? $this->categoryFieldRepository->orderBy('position')->findByField('status', true) : $this->categoryFieldRepository->orderBy('position')->findWhereIn('code', $requestedFields);
    }

    /**
     * Check whether the current category is related to a channel or not.
     * If the category is assigned as root to any channel it can not have parent category.
     *
     * This method will fetch all root category ids from the channel. If `id` is present,
     * then it is not deletable and can not have a parent category.
     */
    protected function isRelatedToChannel(int $categoryId): bool
    {
        return (bool) $this->channelRepository->pluck('root_category_id')->contains($categoryId);
    }
}

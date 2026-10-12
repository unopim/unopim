<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Category\Repositories\CategoryRepository;

class CategoryChildrenForm extends FormRequest
{
    /**
     * Hard ceiling on a page of children so a crafted `limit` cannot pull a
     * whole level of a large catalogue in one request.
     */
    const MAX_PER_PAGE = 200;

    /**
     * Determine if the request is authorized to read the category tree.
     */
    public function authorize(): bool
    {
        abort_unless(bouncer()->hasPermission('catalog.categories'), 403, trans('admin::app.common.unauthorized'));

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id'       => ['required', 'integer', Rule::exists('categories', 'id')],
            'category' => ['nullable', 'integer'],
            'page'     => ['nullable', 'integer', 'min:'.CategoryRepository::DEFAULT_PAGE],
            'limit'    => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'locale'   => ['nullable', 'string', Rule::exists('locales', 'code')],
        ];
    }
}

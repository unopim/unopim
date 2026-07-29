<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryBrowseRequest extends FormRequest
{
    /**
     * Determine if the request is authorized to browse the category tree.
     */
    public function authorize(): bool
    {
        abort_unless(bouncer()->hasPermission('catalog.categories'), 403, trans('admin::app.common.unauthorized'));

        return true;
    }

    /**
     * A category id is only validated as a number: a link to one that has since
     * been deleted should fall back to the bare tree, not bounce the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'view'      => ['nullable', 'string', Rule::in(['tree', 'list'])],
            'panel'     => ['nullable', 'string', Rule::in(['create'])],
            'category'  => ['nullable', 'integer'],
            'parent_id' => ['nullable', 'integer'],
            'locale'    => ['nullable', 'string', Rule::exists('locales', 'code')],
            'history'   => ['nullable', 'boolean'],
        ];
    }

    public function requestedView(): ?string
    {
        return $this->validated('view');
    }

    public function selectedCategoryId(): ?int
    {
        $id = $this->validated('category');

        return $id === null ? null : (int) $id;
    }

    public function parentCategoryId(): ?int
    {
        $id = $this->validated('parent_id');

        return $id === null ? null : (int) $id;
    }

    public function wantsHistory(): bool
    {
        return (bool) $this->validated('history');
    }

    public function wantsCreatePanel(): bool
    {
        return $this->validated('panel') === 'create';
    }
}

<?php

namespace Webkul\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Controllers\VueJsSelect\AbstractOptionsController;

class SelectOptionsForm extends FormRequest
{
    /**
     * Hard ceiling on a page of options so a crafted `limit` cannot pull a whole
     * entity table in one request.
     */
    const MAX_PER_PAGE = 100;

    /**
     * Filter and identifier columns are used as SQL identifiers, so only plain
     * column names are accepted.
     */
    const COLUMN_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    /**
     * Permissions that each grant read access to an entity's options. Holding
     * any one of them is enough.
     */
    const ENTITY_PERMISSIONS = [
        'attributes' => [
            'catalog.attributes',
            'catalog.families',
            'catalog.products',
            'data_transfer.export',
            'data_transfer.imports',
        ],
        'category_fields' => [
            'catalog.category_fields',
            'catalog.categories',
            'data_transfer.export',
            'data_transfer.imports',
        ],
        'channel' => [
            'settings.channels',
            'catalog.products',
            'data_transfer.export',
            'data_transfer.imports',
        ],
        'locale' => [
            'settings.locales',
            'settings.channels',
            'catalog.products',
            'data_transfer.export',
            'data_transfer.imports',
        ],
        'currency' => [
            'settings.currencies',
            'settings.channels',
            'catalog.products',
            'data_transfer.export',
            'data_transfer.imports',
        ],
    ];

    /**
     * Allow the request only when the admin holds a permission for the requested entity.
     */
    public function authorize(): bool
    {
        foreach ($this->permissionsFor((string) $this->input('entityName')) as $permission) {
            if (bouncer()->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function permissionsFor(string $entityName): array
    {
        return static::ENTITY_PERMISSIONS[$entityName] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entityName' => ['required', 'string', Rule::in(AbstractOptionsController::SUPPORTED_ENTITIES)],
            'page'       => ['nullable', 'integer', 'min:1'],
            'limit'      => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'query'      => ['nullable', 'string', 'max:255'],
            'source'     => ['nullable', 'string', 'max:255'],

            'filters'                  => ['nullable', 'array'],
            'filters.*.column'         => ['nullable', 'string', 'regex:'.self::COLUMN_PATTERN],
            'identifiers'              => ['nullable', 'array'],
            'identifiers.columnName'   => ['nullable', 'string', 'regex:'.self::COLUMN_PATTERN],
        ];
    }
}

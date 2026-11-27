<?php

namespace Webkul\Admin\Http\Requests;

class EntitySelectOptionsForm extends SelectOptionsForm
{
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
     * Allow the request only when the admin holds a permission for the entity.
     */
    public function authorize(): bool
    {
        $permissions = $this->permissionsFor((string) $this->query('entityName'));

        if (empty($permissions)) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (bouncer()->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Unknown entities return no permissions and are rejected by the entityName rule instead.
     *
     * @return array<int, string>
     */
    protected function permissionsFor(string $entityName): array
    {
        return static::ENTITY_PERMISSIONS[$entityName] ?? [];
    }
}

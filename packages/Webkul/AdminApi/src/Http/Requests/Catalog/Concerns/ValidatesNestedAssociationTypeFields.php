<?php

namespace Webkul\AdminApi\Http\Requests\Catalog\Concerns;

use Webkul\Category\Rules\ValidationTypes;
use Webkul\Core\Rules\Code;
use Webkul\Product\Rules\AssociationFieldTypes;
use Webkul\Product\Rules\AssociationNotSupportedFields;

/**
 * Rules for the `fields` an association type create/update payload carries.
 *
 * Each entry is written onto an association type field, so it gets the same
 * rules as the single-field endpoints: a valid code and type (required when the
 * type is created, optional on update where an entry may only flag a deletion),
 * an offered validation type and boolean flags. The owning association type is
 * implied by the URL, so an entry may not name one.
 */
trait ValidatesNestedAssociationTypeFields
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function nestedFieldRules(bool $creating): array
    {
        $presence = $creating ? ['required'] : ['sometimes', 'required'];

        return [
            'fields'                       => ['sometimes', 'array'],
            'fields.*'                     => ['array'],
            'fields.*.code'                => [...$presence, new Code, new AssociationNotSupportedFields],
            'fields.*.type'                => [...$presence, new AssociationFieldTypes],
            'fields.*.validation'          => ['sometimes', 'nullable', new ValidationTypes],
            'fields.*.status'              => ['sometimes', 'boolean'],
            'fields.*.is_required'         => ['sometimes', 'boolean'],
            'fields.*.is_unique'           => ['sometimes', 'boolean'],
            'fields.*.value_per_locale'    => ['sometimes', 'boolean'],
            'fields.*.association_type_id' => ['prohibited'],
        ];
    }
}

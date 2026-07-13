@props([
    'values'            => [],
    'attributeRoute'    => '',
    'excludeAttributes' => [],
    'operators'         => [],
    'name'              => 'filters[custom_attributes]',
])

<x-admin::form.field
    type="attribute-conditions"
    :name="$name"
    :value="old('filters.custom_attributes') ?? $values"
    :attribute-route="$attributeRoute"
    :exclude-attributes="json_encode($excludeAttributes)"
    :operators="json_encode($operators)"
    {{ $attributes }}
/>

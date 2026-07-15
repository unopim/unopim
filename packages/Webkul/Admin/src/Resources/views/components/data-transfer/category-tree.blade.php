@props([
    'name'  => 'filters[categories]',
    'value' => [],
])

<x-admin::form.field
    type="category-tree"
    :name="$name"
    :value="$value"
    {{ $attributes }}
/>

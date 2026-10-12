
@props([
    'name'        => '',
    'value'       => '',
    'placeholder' => '',
])

<x-admin::form.field
    type="tags"
    :name="$name"
    :label="$placeholder"
    :value="$value"
    {{ $attributes }}
/>

@props(['types' => []])

<x-admin::form.fields.inputs :types="$types" />

@foreach (array_intersect((array) $types, ['tags', 'category-tree', 'attribute-conditions']) as $type)
    @include('admin::components.form.fields.'.$type)
@endforeach

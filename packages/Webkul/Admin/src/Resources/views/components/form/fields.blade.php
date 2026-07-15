@props([
    'fields'     => [],
    'types'      => [],
    'values'     => [],
    'namePrefix' => 'filters',
    'mode'       => 'form',
    'gridClass'  => '',
    'only'       => '',
    'except'     => '',
])

@php
    $fields = array_values((array) $fields);
@endphp

<x-admin::form.fields.load :types="array_merge(array_column($fields, 'type'), (array) $types)" />

<v-field-set
    :fields='@json($fields)'
    :initial-values='@json((object) $values)'
    name-prefix="{{ $namePrefix }}"
    mode="{{ $mode }}"
    grid-class="{{ $gridClass }}"
    only="{{ is_array($only) ? implode(',', $only) : $only }}"
    except="{{ is_array($except) ? implode(',', $except) : $except }}"
    {{ $attributes }}
></v-field-set>

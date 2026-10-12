@props([
    'field'      => null,
    'type'       => 'text',
    'name'       => null,
    'label'      => null,
    'info'       => null,
    'required'   => false,
    'validation' => null,
    'options'    => null,
    'source'     => null,
    'trackBy'    => null,
    'labelBy'    => null,
    'dependsOn'  => null,
    'value'      => null,
    'namePrefix' => '',
])

@php
    $resolved = $field ?? [
        'name'       => $name,
        'type'       => $type,
        'label'      => $label,
        'info'       => $info,
        'required'   => (bool) $required,
        'validation' => $validation,
        'options'    => $options,
        'async'      => (bool) $source,
        'list_route' => $source,
        'track_by'   => $trackBy,
        'label_by'   => $labelBy,
        'depends_on' => $dependsOn ? ['field' => $dependsOn, 'as' => $dependsOn] : null,
    ];
@endphp

<x-admin::form.fields.load :types="[$resolved['type']]" />

<v-form-field
    :field='@json($resolved)'
    name-prefix="{{ $namePrefix }}"
    :model-value='@json($value)'
    {{ $attributes }}
></v-form-field>

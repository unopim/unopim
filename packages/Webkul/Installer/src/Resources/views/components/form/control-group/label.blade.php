@php
    $labelId = $attributes->hasAny(['for', ':for', 'id', ':id']) ? [] : ['id' => remember_form_label_id()];
@endphp

<label {{ $attributes->merge(['class' => 'block text-[12px] text-gray-800 font-medium leading-[24px]'] + $labelId) }}>
    {{ $slot }}
</label>

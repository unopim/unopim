@props([
    'type'       => 'image',
    'name'       => '',
    'instructions' => '',
])

{!! view_render_event('unopim.admin.media.'.$type.'.before', ['name' => $name]) !!}

{{ $slot }}

@if (! empty(trim($instructions)))
    <p class="mt-1.5 whitespace-pre-line text-xs leading-4 text-gray-500 dark:text-gray-400">{{ $instructions }}</p>
@endif

{!! view_render_event('unopim.admin.media.'.$type.'.after', ['name' => $name]) !!}

{{-- Register the shared leaves once so containers can reference the Vue tags. --}}
<x-admin::media.card v-if="false" />
<x-admin::media.add-tile v-if="false" />

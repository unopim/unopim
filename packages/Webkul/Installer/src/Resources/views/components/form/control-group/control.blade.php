@props([
    'type' => 'text',
    'name' => '',
])

@php
    $labelledBy = 'hidden' === $type ? null : pull_form_label_id();

    $accessibleName = match (true) {
        'hidden' === $type, $attributes->hasAny(['aria-label', ':aria-label', 'aria-labelledby', ':aria-labelledby', 'id']) => [],
        $attributes->has(':label') => [':aria-label' => $attributes->get(':label')],
        $attributes->has('label')  => ['aria-label' => $attributes->get('label')],
        null !== $labelledBy       => ['aria-labelledby' => $labelledBy],
        default                    => [],
    };

    $controlId = (string) $attributes->get('id');

    /** Re-emitted from $controlId so the label sharing this bag does not duplicate the id. */
    $attributes = $attributes->except(['id']);
@endphp

@switch($type)
    @case('hidden')
    @case('text')
    @case('email')
    @case('password')
    @case('number')
        <v-field
            name="{{ $name }}"
            v-slot="{ field }"
            {{ $attributes->only(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
        >
            <input
                @if ('' !== $controlId) id="{{ $controlId }}" @endif
                type="{{ $type }}"
                name="{{ $name }}"
                v-bind="field"
                :class="[errors['{{ $name }}'] ? 'border border-red-600 hover:border-red-600' : '']"
                {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'text-[14px] text-gray-600 appearance-none border rounded-md w-full py-2 px-3 transition-all hover:border-gray-400'] + $accessibleName) }}
            >
        </v-field>

        @break

    @case('select')
        <v-field
            name="{{ $name }}"
            v-slot="{ field }"
            {{ $attributes->only(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
        >
            <select
                @if ('' !== $controlId) id="{{ $controlId }}" @endif
                name="{{ $name }}"
                v-bind="field"
                :class="[errors['{{ $name }}'] ? 'border border-red-500' : '']"
                {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'inline-flex gap-x-1 items-center justify-between text-gray-600 text-[14px] font-normal py-[0.8rem] px-3 w-full bg-white border border-gray-300 rounded-md cursor-pointer transition-all hover:border-gray-400'] + $accessibleName) }}
            >
                {{ $slot }}
            </select>
        </v-field>

        @break

    @case('checkbox')
        <v-field
            v-slot="{ field }"
            name="{{ $name }}"
            type="checkbox"
            class="hidden"
            {{ $attributes->only(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
        >
            <input
                @if ('' !== $controlId) id="{{ $controlId }}" @endif
                type="checkbox"
                name="{{ $name }}"
                v-bind="field"
                class="sr-only peer"
                {{ $attributes->except(['rules', 'label', ':label'])->merge($accessibleName) }}
            />

            <v-checkbox-handler
                :field="field"
                checked="{{ $attributes->get('checked') }}"
            >
            </v-checkbox-handler>
        </v-field>

        <label
            class="icon-checkbox-normal text-[24px] peer-checked:icon-checkbox-active peer-checked:text-primary-700  cursor-pointer"
            {{ $attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label']) }}
        >
        </label>

        @break
@endswitch
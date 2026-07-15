@props([
    'selection',
    'primaryLabel',
    'primaryClick',
    'secondaryLabel' => null,
    'secondaryClick' => null,
    'secondaryIf' => null,
])

<div {{ $attributes->merge(['class' => 'mt-3 flex flex-wrap items-center gap-2']) }}>
    <button
        type="button"
        class="secondary-button text-xs"
        :disabled="! {{ $selection }}.length"
        :class="! {{ $selection }}.length ? 'cursor-not-allowed opacity-60' : ''"
        @click="{{ $primaryClick }}"
    >
        {{ $primaryLabel }}
    </button>

    @if ($secondaryLabel && $secondaryClick)
        <button
            type="button"
            class="secondary-button text-xs"
            :disabled="! {{ $selection }}.length"
            :class="! {{ $selection }}.length ? 'cursor-not-allowed opacity-60' : ''"
            @if ($secondaryIf)
                v-if="{{ $secondaryIf }}"
            @endif
            @click="{{ $secondaryClick }}"
        >
            {{ $secondaryLabel }}
        </button>
    @endif

    <span
        class="text-xs text-gray-500 dark:text-gray-300"
        v-if="{{ $selection }}.length"
        v-text="{{ $selection }}.length + ' selected'"
    >
    </span>
</div>

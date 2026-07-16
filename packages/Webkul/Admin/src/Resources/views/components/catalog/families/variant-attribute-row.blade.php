@props([
    'selection',
    'removable' => false,
])

<div class="relative grid grid-cols-[18px_18px_minmax(0,1fr)_auto] items-center gap-2 rounded-md py-1.5 text-sm text-gray-600 transition-all hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-cherry-800 ltr:ml-12 ltr:pl-2 ltr:pr-2 rtl:mr-12 rtl:pr-2 rtl:pl-2">
    <span class="absolute top-1/2 h-px w-3 bg-gray-200 dark:bg-cherry-800 ltr:-left-5 rtl:-right-5"></span>

    <button
        type="button"
        class="text-2xl leading-none"
        :class="{{ $selection }}.includes(element.code) ? 'icon-checkbox-check text-unopim-primary' : 'icon-checkbox-normal text-gray-500'"
        @click.stop="toggleSelected('{{ $selection }}', element.code)"
    >
    </button>

    <i class="icon-drag cursor-grab text-lg text-gray-400"></i>

    <span
        class="min-w-0 truncate text-sm font-regular transition-all group-hover:text-gray-800 dark:group-hover:text-white max-xl:text-xs"
        v-text="element.label"
    >
    </span>

    @if ($removable)
        <button
            type="button"
            class="icon-cancel ml-auto text-lg text-gray-400 hover:text-red-600"
            @click="moveToCommon(element.code)"
        >
        </button>
    @else
        <span
            class="ml-auto shrink-0 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-500 dark:bg-cherry-800 dark:text-gray-300"
            v-text="element.type"
        >
        </span>
    @endif
</div>

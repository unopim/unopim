@props([
    'list',
    'searchKey',
])

<div
    class="group_node mb-1 mt-2 grid w-full cursor-pointer grid-cols-[24px_24px_minmax(0,1fr)_auto] items-center gap-1.5 rounded-md py-1.5 ltr:pr-2 rtl:pl-2 text-gray-700 transition-all hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-cherry-800"
    v-if="isFirstInGroup(element, {{ $list }}, '{{ $searchKey }}')"
    @click="toggleVariantGroup('{{ $searchKey }}', element.groupCode)"
>
    <button
        type="button"
        class="icon-chevron-down rounded-md text-xl text-gray-400 transition-all hover:bg-white dark:hover:bg-cherry-900"
        :class="{'-rotate-90': isVariantGroupCollapsed('{{ $searchKey }}', element.groupCode)}"
        @click.stop="toggleVariantGroup('{{ $searchKey }}', element.groupCode)"
    >
    </button>

    <i class="icon-folder text-xl text-inherit transition-all"></i>

    <span
        class="min-w-0 truncate text-sm font-medium"
        v-text="element.groupLabel"
    >
    </span>

    <span
        class="rounded-full bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-cherry-800 dark:text-gray-300"
        v-text="groupVisibleCount(element, {{ $list }}, '{{ $searchKey }}')"
    >
    </span>
</div>

@props([
    'id'      => '',
    'title'   => '',
    'icon'    => '',
    'summary' => '',
])

<div
    role="button"
    tabindex="0"
    class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow flex items-center gap-3 cursor-pointer border border-transparent hover:border-unopim-primary-border dark:hover:border-cherry-800 transition-all"
>
    <span class="grid place-items-center w-10 h-10 rounded bg-unopim-primary-soft dark:bg-cherry-800 shrink-0">
        <span class="{{ $icon }} text-2xl text-gray-600 dark:text-gray-300"></span>
    </span>

    <div class="min-w-0 flex-1">
        <p class="flex items-center gap-1.5 text-base font-semibold text-gray-800 dark:text-white truncate">
            {{ $title }}
            <span
                v-show="$productWorkspace?.isDirty('{{ $id }}')"
                class="w-2 h-2 rounded-full bg-amber-500 shrink-0"
                :title="'{{ trans('admin::app.catalog.products.edit.sections.unsaved') }}'"
            ></span>
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
            @if (trim($slot) !== '')
                {{ $slot }}
            @else
                {{ $summary }}
            @endif
        </p>
    </div>

    <span class="text-sm font-medium text-primary-600 dark:text-primary-400 shrink-0">
        @lang('admin::app.catalog.products.edit.sections.view') <span class="rtl:hidden">&rarr;</span>
    </span>
</div>

<div {{ $attributes->merge(['class' => 'relative my-1 ltr:pl-5 rtl:pr-5']) }}>
    <span class="absolute top-1/2 h-px w-5 bg-gray-200 dark:bg-cherry-800 ltr:left-0 rtl:right-0"></span>

    <div class="flex min-h-10 items-center justify-center gap-2 rounded-md border border-dashed border-gray-300 bg-gray-50 px-3 py-2 text-xs font-medium text-gray-500 transition-all dark:border-cherry-800 dark:bg-cherry-900 dark:text-gray-300">
        <span class="icon-add text-lg text-gray-400 dark:text-gray-500"></span>

        <span>
            @lang('admin::app.catalog.families.edit.drop-attributes-here')
        </span>
    </div>
</div>

{{--
    A row inside a variant level card. Axis attributes define the variant, so
    they are read-only: no drag handle (the draggable's handle selector) and no
    remove button — only the badge that says why.
--}}
<div class="group grid grid-cols-[18px_minmax(0,1fr)_auto] items-center gap-2 rounded-md py-1.5 text-sm text-gray-600 transition-all dark:text-gray-300 ltr:ml-10 ltr:pl-2 ltr:pr-2 rtl:mr-10 rtl:pr-2 rtl:pl-2"
    :class="element.locked ? 'bg-gray-50 dark:bg-cherry-800' : 'hover:bg-gray-50 dark:hover:bg-cherry-800'"
>
    <span
        v-if="element.locked"
        class="icon-folder-block text-lg text-gray-400"
        title="{{ trans('admin::app.catalog.families.edit.axis-locked-info') }}"
    >
    </span>

    <i v-else class="icon-drag cursor-grab text-lg text-gray-400"></i>

    <span class="min-w-0 truncate" v-text="element.label"></span>

    <span
        v-if="element.locked"
        class="shrink-0 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 dark:bg-cherry-900 dark:text-amber-300"
    >
        @lang('admin::app.catalog.families.edit.axis-badge')
    </span>

    <button
        v-else
        type="button"
        class="icon-cancel text-lg text-gray-400 hover:text-red-600"
        title="{{ trans('admin::app.catalog.families.edit.move-to-parent') }}"
        @click="moveToCommon(element.code)"
    >
    </button>
</div>

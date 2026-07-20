@props([
    'selectGroupPlaceholder',
])

<div
    v-if="customAttributes.length && selectedAttrs.length"
    class="mb-3 rounded-md border border-unopim-primary-border bg-unopim-primary-soft/50 p-3 dark:border-unopim-primary-900 dark:bg-cherry-800"
>
    <div class="flex items-center gap-2 whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-300">
        <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-unopim-primary px-2 text-xs font-semibold text-white">
            @{{ selectedAttrs.length }}
        </span>

        <span>@lang('admin::app.catalog.families.edit.attributes-selected')</span>
    </div>

    <label class="mt-2.5 block text-xs font-medium text-gray-600 dark:text-gray-300">
        @lang('admin::app.catalog.families.edit.assign-selected-to')
    </label>

    <div class="mt-1">
        <v-select-handler
            name="bulk_group_picker"
            :options="bulkGroupOptions"
            :value="bulkGroupValue"
            placeholder="{{ $selectGroupPlaceholder }}"
            track-by="code"
            label-by="label"
            @input="onBulkGroup"
        >
        </v-select-handler>
    </div>

    <div class="mt-3 flex justify-end">
        <button
            type="button"
            class="primary-button"
            :class="! bulkGroup ? 'cursor-not-allowed opacity-60' : ''"
            :disabled="! bulkGroup"
            @click="assignBulk"
        >
            @lang('admin::app.catalog.families.edit.assign')<template v-if="bulkGroupName"> &rarr; @{{ bulkGroupName }}</template>
        </button>
    </div>
</div>

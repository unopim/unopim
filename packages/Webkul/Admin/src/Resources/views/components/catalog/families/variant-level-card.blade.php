@props([
    'level',
    'number',
    'list',
    'axisLabel',
])

{{-- `number` is a JS expression so the badge follows a level count changed in
     the setup modal without a reload. --}}

<div {{ $attributes->merge(['class' => 'flex min-w-0 flex-col rounded-lg border border-gray-200 dark:border-cherry-800', 'data-variant-level-card' => $level]) }}>
    <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-3 py-2.5 dark:border-cherry-800">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
            <span class="shrink-0 rounded-full bg-unopim-primary-muted px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-unopim-primary dark:bg-cherry-800 dark:text-unopim-primary">
<span v-text="levelBadge({{ $number }})"></span>
            </span>

            <span class="min-w-0 truncate text-sm font-medium text-gray-700 dark:text-gray-200" v-text="{{ $axisLabel }}"></span>
        </div>

        <span
            class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-cherry-800 dark:text-gray-300"
            v-text="{{ $list }}.length"
        >
        </span>
    </div>

    <draggable
        class="grid min-h-[7rem] content-start gap-1 p-2"
        ghost-class="draggable-ghost"
        handle=".icon-drag"
        v-bind="{animation: 200}"
        :list="{{ $list }}"
        item-key="code"
        group="variant-levels"
        @change="syncPlacements"
    >
        <template #item="{ element }">
            <div>
                <x-admin::catalog.families.variant-group-heading
                    :list="$list"
                    :search-key="$level"
                />

                <div v-show="! isVariantGroupCollapsed('{{ $level }}', element.groupCode)">
                    <x-admin::catalog.families.variant-level-row />
                </div>
            </div>
        </template>

        <template #footer>
            <p
                v-if="{{ $list }}.length <= {{ $level === 'sub_parent' ? 'subParentAxisAttributes' : 'variantAxisAttributes' }}.length"
                class="px-2 py-3 text-center text-xs text-gray-400"
            >
                @lang('admin::app.catalog.families.edit.level-empty-info')
            </p>

            <button
                type="button"
                class="secondary-button mt-1 w-full justify-center !py-1.5 text-xs"
                @click="openAddPicker('{{ $level }}')"
            >
                @lang('admin::app.catalog.families.edit.add-attributes')
            </button>
        </template>
    </draggable>
</div>

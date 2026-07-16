@props([
    'title',
    'description',
    'count',
    'countClass' => 'bg-gray-100 text-gray-600 dark:bg-cherry-800 dark:text-gray-300',
    'searching',
    'searchModel',
    'searchKey',
    'list',
    'visibleCount',
    'emptyTitle',
    'emptyDescription',
    'selection',
    'primaryLabel',
    'primaryClick',
    'secondaryLabel' => null,
    'secondaryClick' => null,
    'secondaryIf' => null,
    'removable' => false,
])

<div {{ $attributes->merge(['class' => 'min-w-0', 'data-variant-level-column' => true]) }}>
    <x-admin::list.panel-header
        :title="$title"
        :description="$description"
        :searching="$searching"
    >
        <x-admin::search.field
            icon-position="left"
            :placeholder="trans('admin::app.catalog.families.edit.search')"
            v-model.trim="{{ $searchModel }}"
            clear-when="{{ $searchModel }}"
            clear-action="{{ $searchModel }} = ''"
        />
    </x-admin::list.panel-header>

    <div class="-mt-2 mb-3 flex items-center justify-between gap-3">
        <span
            class="rounded-full px-2 py-0.5 text-xs font-medium {{ $countClass }}"
            v-text="{{ $count }}"
        >
        </span>

        <x-admin::catalog.families.variant-bulk-move
            class="!mt-0 justify-end"
            :selection="$selection"
            :primary-label="$primaryLabel"
            :primary-click="$primaryClick"
            :secondary-label="$secondaryLabel"
            :secondary-click="$secondaryClick"
            :secondary-if="$secondaryIf"
        />
    </div>

    <draggable
        class="grid h-[calc(100vh-285px)] content-start gap-1.5 overflow-auto pb-4 ltr:pr-3 rtl:pl-3"
        ghost-class="draggable-ghost"
        handle=".icon-drag"
        v-bind="{animation: 200}"
        :list="{{ $list }}"
        item-key="code"
        group="variant-levels"
        @change="syncPlacements"
    >
        <template #item="{ element }">
            <div v-show="matchesSearch(element, '{{ $searchKey }}')">
                <x-admin::catalog.families.variant-group-heading
                    :list="$list"
                    :search-key="$searchKey"
                />

                <div v-show="! isVariantGroupCollapsed('{{ $searchKey }}', element.groupCode)">
                    <x-admin::catalog.families.variant-attribute-row
                        :selection="$selection"
                        :removable="$removable"
                    />
                </div>
            </div>
        </template>

        <template #footer>
            <x-admin::list.empty-state
                v-if="! {{ $visibleCount }}"
                :title="$emptyTitle"
                :description="$emptyDescription"
            />
        </template>
    </draggable>
</div>

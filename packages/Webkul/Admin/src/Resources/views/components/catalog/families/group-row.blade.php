@props([
    'removeTitle',
])

<div
    class="group_node grid min-h-9 w-full grid-cols-[22px_22px_minmax(0,1fr)_auto_auto] items-center gap-1.5 rounded-md py-1.5 ltr:pr-2 rtl:pl-2 text-gray-700 transition-all hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-cherry-800"
    :class="{'text-unopim-primary dark:text-unopim-primary': selectedGroup.id == element.id}"
    @click="groupSelected(element)"
>
    <button
        type="button"
        class="icon-chevron-down text-xl rounded-md transition-all hover:bg-white dark:hover:bg-cherry-900"
        :class="{'-rotate-90': element.hide}"
        @click.stop="element.hide = ! element.hide"
    >
    </button>

    <i class="icon-drag text-xl cursor-grab text-gray-500 transition-all hover:text-gray-800 dark:hover:text-white"></i>

    <span class="flex min-w-0 items-center gap-2">
        <i
            class="shrink-0 text-xl text-inherit transition-all"
            :class="[element.is_user_defined ? 'icon-folder' : 'icon-folder-block']"
        >
        </i>

        <span
            class="min-w-0 truncate text-sm font-medium"
            v-text="element.label || element.name"
        >
        </span>
    </span>

    <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-cherry-800 dark:text-gray-300">
        @{{ element.customAttributes.length }}
    </span>

    <input
        v-if="groupFormId(element)"
        type="hidden"
        :name="'attribute_groups[' + groupFormId(element) + '][position]'"
        :value="index + 1"
    />

    <input
        v-if="groupFormId(element)"
        type="hidden"
        :name="'attribute_groups[' + groupFormId(element) + '][attribute_groups_mapping]'"
        v-model="element.group_mapping_id"
    />

    <input
        v-if="groupFormId(element)"
        type="hidden"
        :name="'attribute_groups[' + groupFormId(element) + '][id]'"
        :value="groupFormId(element)"
    />

    <input
        v-if="groupFormId(element)"
        type="hidden"
        :name="'attribute_groups[' + groupFormId(element) + '][code]'"
        :value="element.code"
    />

    <button
        type="button"
        class="icon-delete rounded-md p-1 text-xl transition-all"
        :class="isGroupContainsSku(element) ? 'cursor-not-allowed text-gray-300 dark:text-gray-600' : 'text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950'"
        title="{{ $removeTitle }}"
        @click.stop="removeGroup(element)"
    >
    </button>
</div>

<div class="relative grid min-h-8 grid-cols-[18px_minmax(0,1fr)] items-center gap-2 rounded-md py-1.5 ltr:pl-2 ltr:pr-2 rtl:pr-2 rtl:pl-2 text-gray-600 transition-all hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-cherry-800 group cursor-pointer">
    <span class="absolute top-1/2 h-px w-3 bg-gray-200 dark:bg-cherry-800 ltr:-left-5 rtl:-right-5"></span>

    <i class="icon-drag text-lg text-gray-500 transition-all group-hover:text-gray-700"></i>

    <span
        class="min-w-0 truncate text-sm font-normal leading-5 transition-all group-hover:text-gray-800 dark:group-hover:text-white"
        v-text="element.label || element.name"
    >
    </span>

    <input
        v-if="element.group_id"
        type="hidden"
        :name="'attribute_groups[' + element.group_id + '][custom_attributes][' + index + '][id]'"
        v-model="element.id"
    />

    <input
        v-if="element.group_id"
        type="hidden"
        :name="'attribute_groups[' + element.group_id + '][custom_attributes][' + index + '][position]'"
        :value="index + 1"
    />
</div>

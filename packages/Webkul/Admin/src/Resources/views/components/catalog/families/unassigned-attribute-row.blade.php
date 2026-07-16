<div class="flex items-center gap-2 py-1.5 ltr:pr-1.5 rtl:pl-1.5 rounded text-gray-600 dark:text-gray-300 group">
    <button
        type="button"
        class="text-2xl rounded-md cursor-pointer"
        :class="selectedAttrs.includes(attributeCode(element)) ? 'icon-checkbox-check text-unopim-primary' : 'icon-checkbox-normal text-gray-500'"
        @click.stop="toggleAttr(attributeCode(element))"
    >
    </button>

    <i class="icon-drag text-xl transition-all group-hover:text-gray-800 dark:group-hover:text-white cursor-grab"></i>

    <span
        class="text-sm font-regular transition-all group-hover:text-gray-800 dark:group-hover:text-white max-xl:text-xs"
        v-text="element.label || element.name"
    >
    </span>
</div>

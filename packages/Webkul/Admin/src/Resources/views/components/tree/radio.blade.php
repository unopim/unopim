@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-tree-radio-template"
    >
        <label
            :for="id"
            :class="[
                'inline-flex items-center w-max p-1.5 select-none',
                isCurrentCategory
                    ? 'opacity-40 cursor-not-allowed text-gray-400 dark:text-gray-500'
                    : 'cursor-pointer text-gray-600 dark:text-gray-300'
            ]"
        >
            <input
                type="radio"
                :name="name"
                :value="value"
                :id="id"
                class="hidden peer"
                :checked="isActive"
                :disabled="isCurrentCategory"
                @change="inputChanged()"
            >

            <span
                :class="[
                    'icon-radio-normal mr-1 text-2xl rounded-md peer-checked:icon-radio-selected peer-checked:text-violet-700',
                    isCurrentCategory ? 'cursor-not-allowed' : 'cursor-pointer'
                ]"
            ></span>

            <div
                :class="[
                    'text-sm',
                    isCurrentCategory
                        ? 'cursor-not-allowed'
                        : 'cursor-pointer hover:text-gray-800 dark:hover:text-white'
                ]"
                v-text="label"
            >
            </div>
        </label>
    </script>

    <script type="module">
        app.component('v-tree-radio', {
            template: '#v-tree-radio-template',

            name: 'v-tree-radio',

            props: ['id', 'label', 'name', 'value'],

            inject: ['categorytree'],

            computed: {
                isActive() {
                    return this.$parent.has(this.value);
                },

                isCurrentCategory() {
                    const current = this.categorytree?.currentCategory;

                    return current != null && this.value === current.toString();
                },
            },

            methods: {
                inputChanged() {
                    if (this.isCurrentCategory) {
                        return;
                    }

                    this.$emit('change-input', {
                        id: this.id,
                        label: this.label,
                        name: this.name,
                        value: this.value,
                    });
                },
            },
        });
    </script>
@endPushOnce

@pushOnce('scripts')
    <script type="text/x-template" id="v-spreadsheet-textarea-template">
    <textarea
        ref="input"
        :name="`${entityId}_${column.code}`"
        v-bind="field"
        class="w-full resize-none text-sm text-gray-600 dark:text-gray-300 transition-all  focus:border-gray-400 dark:focus:border-gray-400 bg-transparent dark:border-gray-600"
        @blur="update"
        resize=""
    ></textarea>
    </script>

    <script type="module">
        app.component('v-spreadsheet-textarea', {
            template: '#v-spreadsheet-textarea-template',

            props: {
                isActive: {
                    type: Boolean,
                    default: false,
                },
                modelValue: {
                    type: String,
                    default: null,
                },
                entityId: { 
                    type: Number,
                },
                column : {
                    type: Array,
                },
                attribute : {
                    type: Array,
                }
            },

            data() {
                return {
                    isUpdated: false,
                };
            },

            watch: {
                modelValue(newVal) {
                    if (newVal === this.$refs.input.value) return;

                    this.$refs.input.value = newVal;
                    if (! this.isUpdated) {
                        this.$emitter.emit('update-spreadsheet-data', {
                            value: newVal,
                            entityId: this.entityId,
                            column: this.column,
                        });
                    }

                    this.isUpdated = false;
                },
            },

            mounted() {
                if (this.$refs.input) {
                    this.$refs.input.value = this.modelValue ?? '';
                }
            },

            methods: {
                update() {
                    let updateValue = this.$refs.input.value;

                    this.isUpdated = true;

                    this.$emit('update:modelValue', updateValue);

                    this.$emitter.emit('update-spreadsheet-data', {
                        value: updateValue,
                        entityId: this.entityId,
                        column: this.column,
                    });
                },
            },
        });
    </script>
@endPushOnce

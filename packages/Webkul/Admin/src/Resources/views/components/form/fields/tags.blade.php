@pushOnce('scripts')
    <script type="text/x-template" id="v-field-tags-template">
        <div class="relative w-full">
            <v-multiselect
                :options="tags"
                v-model="tags"
                :multiple="true"
                :taggable="true"
                :searchable="true"
                :close-on-select="false"
                :clear-on-select="true"
                :preserve-search="false"
                :hide-selected="true"
                :show-no-options="false"
                :show-no-results="false"
                :placeholder="placeholder"
                :tag-placeholder="tagPlaceholder"
                :disabled="disabled"
                :name="name"
                :id="inputId"
                @tag="addTag"
                @search-change="onSearchChange"
            >
                <template v-slot:clear>
                    <span
                        v-if="tags.length && ! disabled"
                        role="button"
                        tabindex="0"
                        :aria-label="clearLabel"
                        :title="clearLabel"
                        @mousedown.prevent.stop="clearTags"
                        @keydown.enter.prevent.stop="clearTags"
                        class="icon-cancel absolute right-7 top-2.5 z-10 cursor-pointer text-xl leading-none text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"
                    ></span>
                </template>
            </v-multiselect>

            <input type="hidden" :name="name" :value="serialized" />
        </div>
    </script>

    <script type="module">
        const TAG_SEPARATOR = /[\r\n\t;,]+/;

        app.component('v-field-tags', {
            template: '#v-field-tags-template',

            mixins: [window.unopim.fieldBase],

            data() {
                return {
                    tags: this.splitTags(this.modelValue),
                };
            },

            computed: {
                serialized() {
                    return this.tags.join(',');
                },

                placeholder() {
                    return this.field.placeholder ?? @json(trans('admin::app.components.form.tags.placeholder'));
                },

                clearLabel() {
                    return @json(trans('admin::app.components.form.tags.clear-all'));
                },

                tagPlaceholder() {
                    return @json(trans('admin::app.components.form.tags.tag-placeholder'));
                },
            },

            watch: {
                tags: {
                    deep: true,
                    handler() {
                        this.setValue(this.serialized);
                    },
                },
            },

            methods: {
                splitTags(value) {
                    if (value === null || value === undefined) {
                        return [];
                    }

                    const tags = [];

                    const source = Array.isArray(value) ? value.join(',') : `${value}`;

                    source.split(TAG_SEPARATOR).forEach(part => {
                        const tag = part.trim();

                        if (tag && ! tags.includes(tag)) {
                            tags.push(tag);
                        }
                    });

                    return tags;
                },

                clearTags() {
                    this.tags = [];
                },

                addTag(tag) {
                    this.splitTags(tag).forEach(value => {
                        if (! this.tags.includes(value)) {
                            this.tags.push(value);
                        }
                    });
                },

                /**
                 * Pasting a comma or newline separated list commits every value at once,
                 * so the field accepts a column copied straight out of a spreadsheet.
                 */
                onSearchChange(query) {
                    if (! TAG_SEPARATOR.test(query)) {
                        return;
                    }

                    this.addTag(query);

                    this.$el.querySelector('.multiselect__input').value = '';
                },
            },
        });
    </script>
@endPushOnce

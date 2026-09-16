@pushOnce('scripts')
    <script type="text/x-template" id="v-field-tags-template">
        <div
            class="multiselect relative w-full"
            :class="{'multiselect--active': focused, 'multiselect--disabled': disabled}"
        >
            <div
                class="multiselect__tags !flex !flex-wrap !items-center gap-y-1 cursor-text"
                :class="hasErrors ? 'border !border-danger' : ''"
                @click="focusInput"
            >
                <span
                    v-for="(tag, index) in tags"
                    :key="tag"
                    class="multiselect__tag !mb-0 !mr-0 flex items-center"
                >
                    <span
                        class="inline-block max-w-[220px] truncate align-bottom"
                        :title="tag"
                        v-text="tag"
                    ></span>

                    <i
                        class="multiselect__tag-icon"
                        role="button"
                        tabindex="-1"
                        :aria-label="removeLabel(tag)"
                        :title="removeLabel(tag)"
                        @click.stop="removeTag(index)"
                    ></i>
                </span>

                <input
                    ref="input"
                    type="text"
                    class="multiselect__input !mb-0 !w-auto !min-w-0 flex-1 basis-0 !pl-0"
                    :id="inputId"
                    v-model="draft"
                    :placeholder="tags.length ? '' : placeholder"
                    :disabled="disabled"
                    :aria-invalid="hasErrors"
                    @keydown="onKeydown"
                    @paste="onPaste"
                    @focus="focused = true"
                    @blur="onBlur"
                />

                <input type="hidden" :name="name" :value="serialized" />
            </div>

            <button
                v-if="tags.length && ! disabled"
                type="button"
                :aria-label="clearLabel"
                :title="clearLabel"
                @click.stop="clearTags"
                class="icon-cancel absolute right-2 top-2 flex items-center rounded text-lg leading-none text-gray-500 transition-all hover:bg-gray-100 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-cherry-800 dark:hover:text-white"
            ></button>
        </div>
    </script>

    <script type="module">
        const TAG_SEPARATOR = /[\r\n\t;,]+/;
        const TAB_KEY = 'Tab';
        const REMOVE_KEY = 'Backspace';
        const COMMIT_KEYS = ['Enter', ',', ';', TAB_KEY];

        app.component('v-field-tags', {
            template: '#v-field-tags-template',

            mixins: [window.unopim.fieldBase],

            data() {
                return {
                    tags: this.splitTags(this.modelValue),
                    draft: '',
                    focused: false,
                };
            },

            computed: {
                serialized() {
                    return this.tags.join(',');
                },

                clearLabel() {
                    return @json(trans('admin::app.components.form.tags.clear-all'));
                },

                placeholder() {
                    return this.field.placeholder ?? @json(trans('admin::app.components.form.tags.placeholder'));
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

                addTag(tag) {
                    const value = `${tag ?? ''}`.trim();

                    if (! value || this.tags.includes(value)) {
                        return;
                    }

                    this.tags.push(value);
                },

                clearTags() {
                    this.tags = [];

                    this.draft = '';

                    this.focusInput();
                },

                removeTag(index) {
                    this.tags.splice(index, 1);
                },

                removeLabel(tag) {
                    return @json(trans('admin::app.components.form.tags.remove')).replace(':value', tag);
                },

                onBlur() {
                    this.focused = false;

                    this.commitDraft();
                },

                commitDraft() {
                    this.splitTags(this.draft).forEach(tag => this.addTag(tag));

                    this.draft = '';
                },

                onKeydown(event) {
                    if (event.key === REMOVE_KEY && this.draft === '' && this.tags.length) {
                        this.removeTag(this.tags.length - 1);

                        return;
                    }

                    if (! COMMIT_KEYS.includes(event.key) || this.draft.trim() === '') {
                        return;
                    }

                    if (event.key !== TAB_KEY) {
                        event.preventDefault();
                    }

                    this.commitDraft();
                },

                onPaste(event) {
                    event.preventDefault();

                    const pasted = (event.clipboardData || window.clipboardData).getData('text');

                    this.splitTags(pasted).forEach(tag => this.addTag(tag));
                },

                focusInput() {
                    this.$refs.input?.focus();
                },
            },
        });
    </script>
@endPushOnce

@pushOnce('scripts')
    <script type="text/x-template" id="v-field-tags-template">
        <div class="flex w-full flex-col gap-1.5">
            <div
                class="flex w-full cursor-text flex-col gap-2 overflow-y-auto rounded-md border px-3 py-2.5 transition-all min-h-[104px] max-h-[220px] hover:border-gray-400 focus-within:border-gray-400 dark:border-gray-600 dark:bg-cherry-900 dark:hover:border-gray-400"
                :class="hasErrors ? 'border !border-danger' : ''"
                @click="focusInput"
            >
                <div
                    v-if="tags.length"
                    class="flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="(tag, index) in tags"
                        :key="tag"
                        class="inline-flex max-w-full items-center gap-1 rounded-md border border-gray-200 bg-gray-100 py-0.5 pl-2 pr-1 text-xs text-gray-800 dark:border-gray-800 dark:bg-cherry-800 dark:text-gray-300"
                    >
                        <span
                            class="truncate max-w-[220px]"
                            :title="tag"
                            v-text="tag"
                        ></span>

                        <button
                            type="button"
                            :aria-label="removeLabel(tag)"
                            :title="removeLabel(tag)"
                            :disabled="disabled"
                            @click.stop="removeTag(index)"
                            class="icon-cross-large flex shrink-0 items-center rounded text-sm leading-none text-gray-500 hover:bg-gray-200 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-cherry-900 dark:hover:text-white"
                        >
                        </button>
                    </span>
                </div>

                <input
                    ref="input"
                    type="text"
                    :id="inputId"
                    v-model="draft"
                    :placeholder="placeholder"
                    :disabled="disabled"
                    :aria-invalid="hasErrors"
                    class="w-full flex-1 border-0 bg-transparent px-0 py-0.5 text-sm text-gray-600 focus:outline-none focus:ring-0 dark:text-gray-300"
                    @keydown="onKeydown"
                    @paste="onPaste"
                    @blur="commitDraft"
                />

                <input type="hidden" :name="name" :value="serialized" />
            </div>

            <div class="flex items-center justify-between gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400" v-text="footerLabel"></span>

                <button
                    v-if="tags.length"
                    type="button"
                    :disabled="disabled"
                    @click="clearTags"
                    class="text-xs text-info transition-all hover:underline"
                >
                    @lang('admin::app.components.form.tags.clear-all')
                </button>
            </div>
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
                };
            },

            computed: {
                serialized() {
                    return this.tags.join(',');
                },

                placeholder() {
                    return this.field.placeholder ?? @json(trans('admin::app.components.form.tags.placeholder'));
                },

                footerLabel() {
                    if (! this.tags.length) {
                        return @json(trans('admin::app.components.form.tags.hint'));
                    }

                    const key = this.tags.length === 1
                        ? @json(trans('admin::app.components.form.tags.count-one'))
                        : @json(trans('admin::app.components.form.tags.count'));

                    return key.replace(':count', this.tags.length);
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

                removeTag(index) {
                    this.tags.splice(index, 1);
                },

                clearTags() {
                    this.tags = [];

                    this.draft = '';

                    this.focusInput();
                },

                removeLabel(tag) {
                    return @json(trans('admin::app.components.form.tags.remove')).replace(':value', tag);
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

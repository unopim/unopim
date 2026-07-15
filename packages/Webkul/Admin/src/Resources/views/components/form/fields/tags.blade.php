@pushOnce('scripts')
    <script type="text/x-template" id="v-field-tags-template">
        <div
            class="flex flex-wrap items-center gap-2 w-full min-h-[44px] px-3 py-2.5 border rounded-md text-sm transition-all hover:border-gray-400 dark:hover:border-gray-400 focus-within:border-gray-400 dark:bg-cherry-900 dark:border-gray-600 cursor-text"
            :class="hasErrors ? 'border !border-red-600' : ''"
            @click="focusInput"
        >
            <span
                v-for="(tag, index) in tags"
                :key="tag"
                class="flex items-center gap-1 px-1 py-1 rounded bg-primary-100 dark:bg-cherry-800 text-primary-700 dark:text-primary-200 text-sm"
            >
                <span v-text="tag"></span>

                <button
                    type="button"
                    :aria-label="'Remove ' + tag"
                    @click.stop="removeTag(index)"
                    class="icon-cross-large text-base leading-none text-primary-500 dark:text-primary-300 hover:text-primary-700 dark:hover:text-white"
                >
                </button>
            </span>

            <input
                ref="input"
                type="text"
                :id="inputId"
                v-model="draft"
                :placeholder="tags.length ? '' : (field.placeholder ?? field.label)"
                :disabled="disabled"
                :aria-invalid="hasErrors"
                class="flex-1 min-w-[60px] px-0 py-0.5 bg-transparent border-0 text-sm text-gray-600 dark:text-gray-300 focus:ring-0 focus:outline-none"
                @keydown="onKeydown"
                @paste="onPaste"
                @blur="commitDraft"
            />

            <input type="hidden" :name="name" :value="serialized" />
        </div>
    </script>

    <script type="module">
        const TAG_SEPARATOR = /[\s,]+/;
        const TAB_KEY = 'Tab';
        const REMOVE_KEY = 'Backspace';
        const COMMIT_KEYS = ['Enter', ',', ' ', TAB_KEY];

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

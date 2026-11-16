@props(['productId', 'field', 'fieldName', 'localeCode', 'currencies' => [], 'filters' => [], 'rich' => false])

<v-product-template-assist
    suggestions-url="{{ route('admin.catalog.products.template_suggestions', ['id' => $productId]) }}"
    attribute="{{ $field->code }}"
    field-name="{{ $fieldName }}"
    locale="{{ $localeCode }}"
    :currencies='@json($currencies)'
    :filters='@json($filters)'
    :rich='@json((bool) $rich)'
></v-product-template-assist>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-product-template-assist-template"
    >
        <span class="inline-flex">
            <x-admin::dropdown
                position="bottom-right"
                :teleport="true"
            >
                <x-slot:toggle>
                    <button
                        type="button"
                        class="icon-information text-base text-gray-500 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white cursor-pointer"
                        title="@lang('admin::app.catalog.products.edit.templates.help')"
                        aria-label="@lang('admin::app.catalog.products.edit.templates.help')"
                    ></button>
                </x-slot>

                <x-slot:content class="!p-4 w-72 max-w-[calc(100vw-2rem)] flex flex-col gap-2 text-xs text-gray-600 dark:text-gray-300">
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.products.edit.templates.title')
                    </p>

                    <p>@lang('admin::app.catalog.products.edit.templates.info')</p>

                    <p class="font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.products.edit.templates.example')
                    </p>

                    <code
                        class="self-start px-1.5 py-0.5 rounded bg-gray-100 dark:bg-cherry-800 font-mono text-gray-800 dark:text-white"
                        v-pre
                    >@{{ name | upper }}</code>

                    <p class="font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.products.edit.templates.filters')
                    </p>

                    <div
                        class="flex flex-wrap gap-1"
                        v-pre
                    >
                        @foreach (['upper', 'lower', 'capitalize', 'title', 'slug', "format_date('d.m.Y')"] as $filter)
                            <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-cherry-800 font-mono text-gray-800 dark:text-white">{{ $filter }}</code>
                        @endforeach
                    </div>

                    <p class="font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.products.edit.templates.currency')
                    </p>

                    <code
                        class="self-start px-1.5 py-0.5 rounded bg-gray-100 dark:bg-cherry-800 font-mono text-gray-800 dark:text-white"
                        v-pre
                    >@{{ price.EUR }}</code>
                </x-slot>
            </x-admin::dropdown>
        </span>
    </script>

    <script type="module">
        const templateAttributeTypes = new Map();

        app.component('v-product-template-assist', {
            template: '#v-product-template-assist-template',

            props: {
                suggestionsUrl: {
                    type: String,
                    required: true,
                },

                attribute: {
                    type: String,
                    required: true,
                },

                fieldName: {
                    type: String,
                    required: true,
                },

                locale: {
                    type: String,
                    required: true,
                },

                currencies: {
                    type: Array,
                    default: () => [],
                },

                filters: {
                    type: Array,
                    default: () => [],
                },

                rich: {
                    type: Boolean,
                    default: false,
                },
            },

            data() {
                return {
                    input: null,

                    tribute: null,

                    query: null,
                };
            },

            mounted() {
                if (this.rich) {
                    return;
                }

                this.$nextTick(() => {
                    this.input = this.$el
                        .closest('[data-control-group]')
                        ?.querySelector(`[name="${CSS.escape(this.fieldName)}"]`);

                    if (! this.input) {
                        return;
                    }

                    this.tribute = this.$tribute.init({
                        noMatchTemplate: () => this.tribute?.current?.collection?.trigger === '@'
                            ? this.menuItem(@json(trans('admin::app.common.no-match-found')))
                            : '',
                        collection: [
                            {
                                trigger: '@',
                                requireLeadingSpace: true,
                                values: this.fetchSuggestions,
                                lookup: (item) => `${item.name} ${item.code}`,
                                fillAttr: 'code',
                                selectTemplate: (item) => item ? `@{{ ${item.original.code} }}` : null,
                                menuItemTemplate: (item) => this.menuItem(item.original.name, item.original.code),
                            },
                            {
                                trigger: '.',
                                requireLeadingSpace: false,
                                values: this.fetchCurrencies,
                                lookup: 'code',
                                fillAttr: 'code',
                                selectTemplate: (item) => item ? `.${item.original.code}` : null,
                                menuItemTemplate: (item) => this.menuItem(item.original.code, item.original.symbol),
                            },
                            {
                                trigger: '|',
                                requireLeadingSpace: false,
                                values: this.fetchFilters,
                                lookup: 'name',
                                fillAttr: 'name',
                                selectTemplate: (item) => item ? this.filterCall(item.original.value) : null,
                                menuItemTemplate: (item) => this.menuItem(
                                    item.original.name,
                                    item.original.value === item.original.name ? null : item.original.value,
                                ),
                            },
                        ],
                    });

                    this.tribute.attach(this.input);

                    this.input.addEventListener('tribute-replaced', this.tidySpacing);

                    this.input.addEventListener('focus', this.loadAttributeTypes, { once: true });
                });
            },

            beforeUnmount() {
                if (this.tribute && this.input) {
                    this.tribute.detach(this.input);

                    this.input.removeEventListener('tribute-replaced', this.tidySpacing);

                    this.input.removeEventListener('focus', this.loadAttributeTypes);
                }
            },

            methods: {
                fetchCurrencies(text, callback) {
                    this.tribute.current.filteredItems = [];

                    const before = this.input.value.slice(0, this.input.selectionStart - text.length - 1);

                    const code = before.match(/\{\{\s*(\w+)$/)?.[1];

                    if (! code || ! this.currencies.length) {
                        callback([]);

                        return;
                    }

                    this.loadAttributeTypes().then((types) => {
                        callback(types[code] === 'price' ? this.currencies : []);
                    });
                },

                fetchFilters(text, callback) {
                    this.tribute.current.filteredItems = [];

                    const before = this.input.value.slice(0, this.input.selectionStart - text.length - 1);

                    const expression = before.match(/\{\{\s*(\w+)(?:\.\w+)?\s*((?:\|\s*\w+\s*(?:\([^)]*\)\s*)?)*)$/);

                    if (! expression || ! this.filters.length) {
                        callback([]);

                        return;
                    }

                    const chained = expression[2].includes('|');

                    this.loadAttributeTypes().then((types) => {
                        const type = types[expression[1]];

                        callback(type
                            ? this.filters.filter((filter) => filter.types.includes(type) && (! chained || filter.types.includes('text')))
                            : []);
                    });
                },

                loadAttributeTypes() {
                    if (! templateAttributeTypes.has(this.suggestionsUrl)) {
                        templateAttributeTypes.set(this.suggestionsUrl, this.$axios
                            .get(this.suggestionsUrl, {
                                params: {
                                    locale: this.locale,
                                },
                            })
                            .then(({ data }) => Object.fromEntries(data.map((item) => [item.code, item.type])))
                            .catch(() => {
                                templateAttributeTypes.delete(this.suggestionsUrl);

                                return {};
                            }));
                    }

                    return templateAttributeTypes.get(this.suggestionsUrl);
                },

                filterCall(value) {
                    const trigger = this.input.selectionStart - this.tribute.current.mentionText.length - 1;

                    const spacer = /\s/.test(this.input.value.charAt(trigger - 1)) ? '' : ' ';

                    return `${spacer}| ${value}`;
                },

                tidySpacing() {
                    const position = this.input.selectionStart;

                    if (this.input.value.slice(position - 1, position + 1) !== '  ') {
                        return;
                    }

                    this.input.value = this.input.value.slice(0, position - 1) + this.input.value.slice(position);

                    this.input.setSelectionRange(position, position);

                    this.input.dispatchEvent(new Event('input', { bubbles: true }));
                },

                fetchSuggestions(text, callback) {
                    this.tribute.current.filteredItems = [];

                    this.query = text;

                    this.$axios
                        .get(this.suggestionsUrl, {
                            params: {
                                attribute: this.attribute,
                                query: text,
                                locale: this.locale,
                            },
                        })
                        .then(({ data }) => {
                            if (this.query === text) {
                                callback(data);
                            }
                        })
                        .catch(() => {
                            if (this.query === text) {
                                callback([]);
                            }
                        });
                },

                menuItem(label, code = null) {
                    const element = document.createElement('div');

                    element.className = 'flex justify-between gap-3 p-1.5 rounded-md text-sm cursor-pointer';

                    const name = document.createElement('span');

                    name.textContent = label;

                    element.appendChild(name);

                    if (code) {
                        const codeLabel = document.createElement('span');

                        codeLabel.className = 'font-mono text-xs text-gray-500 dark:text-gray-400';

                        codeLabel.textContent = code;

                        element.appendChild(codeLabel);
                    }

                    return element.outerHTML;
                },
            },
        });
    </script>
@endPushOnce

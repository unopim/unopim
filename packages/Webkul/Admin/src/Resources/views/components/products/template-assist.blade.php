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
                        @foreach (array_column($filters, 'value') as $filter)
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

                    latestSuggestions: null,
                };
            },

            mounted() {
                if (this.rich) {
                    window.tinymce?.on('SetupEditor', this.setupEditor);

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
                                selectTemplate: (item) => item
                                    ? this.filterCall(this.textBeforeTrigger(this.tribute.current.mentionText), item.original.value)
                                    : null,
                                menuItemTemplate: (item) => this.menuItem(
                                    item.original.name,
                                    item.original.value === item.original.name ? null : item.original.value,
                                ),
                            },
                        ],
                    });

                    this.tribute.attach(this.input);

                    this.input.addEventListener('tribute-replaced', this.tidySpacing);
                });
            },

            beforeUnmount() {
                window.tinymce?.off('SetupEditor', this.setupEditor);

                if (this.tribute && this.input) {
                    this.tribute.detach(this.input);

                    this.input.removeEventListener('tribute-replaced', this.tidySpacing);
                }
            },

            methods: {
                setupEditor({ editor }) {
                    const element = editor.getElement();

                    if (
                        element?.name !== this.fieldName
                        || ! this.$el.closest('[data-control-group]')?.contains(element)
                    ) {
                        return;
                    }

                    let before = '';

                    let open = false;

                    const remember = (range) => {
                        const preceding = editor.dom.createRng();

                        preceding.setStart(editor.dom.getParent(range.startContainer, editor.dom.isBlock) ?? editor.getBody(), 0);

                        preceding.setEnd(range.startContainer, range.startOffset);

                        before = preceding.toString();

                        return before;
                    };

                    const insert = (api, range, text) => {
                        editor.selection.setRng(range);

                        editor.insertContent(editor.dom.encode(text));

                        api.hide();
                    };

                    this.styleEditorMenu();

                    editor.ui.registry.addAutocompleter('templateAttributes', {
                        trigger: '@',
                        minChars: 0,
                        columns: 1,
                        highlightOn: ['label', 'detail'],
                        fetch: (pattern) => this.latestAttributeSuggestions(pattern)
                            .then((items) => items.map((item) => this.editorItem(item.code, item.name, item.code))),
                        onAction: (api, range, value) => insert(api, range, `@{{ ${value} }}`),
                    });

                    editor.ui.registry.addAutocompleter('templateCurrencies', {
                        trigger: '.',
                        minChars: 0,
                        columns: 1,
                        highlightOn: ['label'],
                        matches: (range) => this.currencyCode(remember(range)) !== null,
                        fetch: (pattern) => this.currencyOptions(this.currencyCode(before))
                            .then((currencies) => currencies
                                .filter((currency) => this.contains(currency.code, pattern))
                                .map((currency) => this.editorItem(currency.code, currency.code, currency.symbol))),
                        onAction: (api, range, value) => insert(api, range, `.${value}`),
                    });

                    editor.ui.registry.addAutocompleter('templateFilters', {
                        trigger: '|',
                        minChars: 0,
                        columns: 1,
                        highlightOn: ['label'],
                        matches: (range) => this.filterExpression(remember(range)) !== null,
                        fetch: (pattern) => this.filterOptions(this.filterExpression(before))
                            .then((filters) => filters
                                .filter((filter) => this.contains(filter.name, pattern))
                                .map((filter) => this.editorItem(
                                    filter.value,
                                    filter.name,
                                    filter.value === filter.name ? null : filter.value,
                                ))),
                        onAction: (api, range, value) => insert(api, range, this.filterCall(remember(range), value)),
                    });

                    editor.on('AutocompleterStart', () => {
                        open = true;
                    });

                    editor.on('AutocompleterEnd', () => {
                        open = false;
                    });

                    editor.on('keydown', (event) => {
                        if (! open || event.key !== 'Tab' || event.shiftKey) {
                            return;
                        }

                        const item = document.querySelector('.tox-autocompleter .tox-collection__item--active');

                        if (! item) {
                            return;
                        }

                        event.preventDefault();

                        event.stopImmediatePropagation();

                        item.click();
                    });
                },

                editorItem(value, label, detail = null) {
                    const items = [{ type: 'cardtext', name: 'label', text: label }];

                    if (detail) {
                        items.push({
                            type: 'cardtext',
                            name: 'detail',
                            text: detail,
                            classes: ['tox-collection__item-accessory', 'product-template-assist-detail'],
                        });
                    }

                    return {
                        type: 'cardmenuitem',
                        value,
                        label,
                        items,
                    };
                },

                styleEditorMenu() {
                    if (document.getElementById('product-template-assist-menu')) {
                        return;
                    }

                    const style = document.createElement('style');

                    style.id = 'product-template-assist-menu';

                    style.textContent = [
                        '.tox .tox-autocompleter, .tox .tox-autocompleter .tox-menu { min-width: 18rem !important; max-width: 30rem !important; }',
                        '.tox-autocompleter .tox-menu { max-height: 15rem !important; overflow-y: auto !important; }',
                        '.tox .tox-autocompleter .product-template-assist-detail { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 12px; white-space: nowrap; }',
                    ].join(' ');

                    document.head.appendChild(style);
                },

                contains(value, pattern) {
                    return value.toLowerCase().includes(pattern.toLowerCase());
                },

                textBeforeTrigger(text) {
                    return this.input.value.slice(0, this.input.selectionStart - text.length - 1);
                },

                currencyCode(before) {
                    return before.match(/\{\{\s*(\w+)$/)?.[1] ?? null;
                },

                filterExpression(before) {
                    const expression = before.match(/\{\{\s*(\w+)(?:\.\w+)?\s*((?:\|\s*\w+\s*(?:\([^)]*\)\s*)?)*)$/);

                    return expression
                        ? { code: expression[1], chained: expression[2].includes('|') }
                        : null;
                },

                currencyOptions(code) {
                    if (! code || ! this.currencies.length) {
                        return Promise.resolve([]);
                    }

                    return this.attributeType(code)
                        .then((type) => type === 'price' ? this.currencies : []);
                },

                filterOptions(expression) {
                    if (! expression || ! this.filters.length) {
                        return Promise.resolve([]);
                    }

                    return this.attributeType(expression.code).then((type) => type
                        ? this.filters.filter((filter) => filter.types.includes(type) && (! expression.chained || filter.types.includes('text')))
                        : []);
                },

                fetchCurrencies(text, callback) {
                    this.tribute.current.filteredItems = [];

                    this.currencyOptions(this.currencyCode(this.textBeforeTrigger(text))).then(callback);
                },

                fetchFilters(text, callback) {
                    this.tribute.current.filteredItems = [];

                    this.filterOptions(this.filterExpression(this.textBeforeTrigger(text))).then(callback);
                },

                attributeType(code) {
                    const key = `${this.suggestionsUrl}|${code}`;

                    if (! templateAttributeTypes.has(key)) {
                        templateAttributeTypes.set(key, this.$axios
                            .get(this.suggestionsUrl, {
                                params: {
                                    code,
                                },
                            })
                            .then(({ data }) => data.find((item) => item.code === code)?.type ?? null)
                            .catch(() => {
                                templateAttributeTypes.delete(key);

                                return null;
                            }));
                    }

                    return templateAttributeTypes.get(key);
                },

                filterCall(before, value) {
                    return `${/\s$/.test(before) ? '' : ' '}| ${value}`;
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

                attributeSuggestions(text) {
                    return this.$axios
                        .get(this.suggestionsUrl, {
                            params: {
                                attribute: this.attribute,
                                query: text,
                                locale: this.locale,
                            },
                        })
                        .then(({ data }) => data)
                        .catch(() => []);
                },

                latestAttributeSuggestions(text) {
                    const request = this.attributeSuggestions(text);

                    this.latestSuggestions = request;

                    return request.then(() => this.latestSuggestions);
                },

                fetchSuggestions(text, callback) {
                    this.tribute.current.filteredItems = [];

                    this.query = text;

                    this.attributeSuggestions(text).then((data) => {
                        if (this.query === text) {
                            callback(data);
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

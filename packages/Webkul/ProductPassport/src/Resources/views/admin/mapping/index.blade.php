<x-admin::layouts>
    <x-slot:title>
        @lang('passport::app.mapping.title')
    </x-slot>

    <x-admin::page-header
        :title="trans('passport::app.mapping.title')"
        :back="route('admin.catalog.passports.index')"
    />

    @include('passport::admin.partials.tabs', ['active' => 'mapping'])

    <x-admin::form
        :action="route('admin.catalog.passports.mapping.update')"
        method="PUT"
        :ajax="true"
    >
        {{-- Section A — fixed regulatory DPP fields (members of the `dpp` group),
             each sourced from an existing attribute. Persisted per field at
             `catalog.product_passport.mapping.<dpp_code>`. --}}
        <div class="mt-4 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                @lang('passport::app.mapping.regulatory-heading')
            </p>

            <div class="mt-4 grid gap-4">
                @if ($passportFields->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-gray-300 italic">
                        @lang('passport::app.mapping.regulatory-empty')
                    </p>
                @endif

                @foreach ($passportFields as $field)
                    {{-- 2-column row: field name on the left, source select on the right;
                         stacks vertically on small screens (desktop-first max-sm idiom). --}}
                    <x-admin::form.control-group
                        class="!mb-0 grid grid-cols-2 max-sm:grid-cols-1 gap-x-6 gap-y-2 items-center"
                    >
                        <x-admin::form.control-group.label class="!mb-0">
                            {{ $field->getTranslatedValueWithFallback('name') ?: $field->code }}
                        </x-admin::form.control-group.label>

                        <div>
                            <x-admin::form.control-group.control
                                type="select"
                                async="true"
                                entity-name="attributes"
                                track-by="code"
                                label-by="label"
                                :name="'mapping[' . $field->code . ']'"
                                :value="$mapping[$field->code] ?? ''"
                                :label="$field->getTranslatedValueWithFallback('name') ?: $field->code"
                                :placeholder="trans('passport::app.mapping.select-source')"
                                :query-params="json_encode($sourceParams[$field->code] ?? [])"
                            />

                            <x-admin::form.control-group.error :control-name="'mapping[' . $field->code . ']'" />
                        </div>
                    </x-admin::form.control-group>
                @endforeach
            </div>
        </div>

        {{-- Section B — merchant-defined custom fields: a reactive row list where
             each row is a typed label plus a source attribute. Rows post as
             `custom_fields[i][name|attribute]` and persist as one JSON row at
             `catalog.product_passport.custom_fields`. --}}
        <div class="mt-4 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                @lang('passport::app.mapping.custom-heading')
            </p>

            <p class="mt-1 text-xs text-gray-400 dark:text-gray-300">
                @lang('passport::app.mapping.custom-info')
            </p>

            <v-passport-custom-fields
                class="mt-4 block"
                :initial='@json($customFields)'
                :source-params='@json($customSourceParams)'
            ></v-passport-custom-fields>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-passport-source-select-template">
            <v-multiselect
                :options="options"
                track-by="code"
                label="label"
                :searchable="true"
                :internal-search="false"
                :loading="isLoading"
                :preserve-search="false"
                :close-on-select="true"
                :clear-on-select="true"
                :show-no-results="true"
                :hide-selected="false"
                :placeholder="placeholder"
                v-model="selected"
                @search-change="search"
            >
            </v-multiselect>
        </script>

        <script type="text/x-template" id="v-passport-custom-fields-template">
            <div class="grid gap-4">
                <div
                    v-for="(row, index) in rows"
                    :key="row.uid"
                    class="grid grid-cols-2 max-sm:grid-cols-1 gap-x-6 gap-y-2 items-center"
                >
                    <input
                        type="text"
                        :name="'custom_fields[' + index + '][name]'"
                        v-model="row.name"
                        :placeholder="namePlaceholder"
                        :aria-label="namePlaceholder"
                        class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-900 dark:border-gray-600"
                    />

                    <div class="flex items-center gap-2.5">
                        <div class="flex-1 min-w-0">
                            <v-passport-source-select
                                :value="row.attribute"
                                :initial-label="row.label"
                                :placeholder="sourcePlaceholder"
                                :query-params="sourceParamsObject"
                                @input="setAttribute(index, $event)"
                            >
                            </v-passport-source-select>
                        </div>

                        <input type="hidden" :name="'custom_fields[' + index + '][attribute]'" :value="row.attribute" />

                        <button
                            type="button"
                            @click="removeRow(index)"
                            :title="removeTitle"
                            :aria-label="removeTitle"
                            class="icon-delete shrink-0 self-center text-2xl text-gray-400 dark:text-gray-300 cursor-pointer rounded-md hover:bg-gray-100 hover:text-red-500 dark:hover:bg-cherry-800 p-1"
                        >
                        </button>
                    </div>
                </div>

                <p v-if="! rows.length" class="text-sm text-gray-400 dark:text-gray-300 italic">
                    @lang('passport::app.mapping.custom-empty')
                </p>

                <div>
                    <button
                        type="button"
                        @click="addRow"
                        class="flex items-center justify-center gap-2 w-full py-3 border-2 border-dashed border-primary-300 rounded-lg bg-primary-50 dark:bg-cherry-800 text-primary-700 dark:text-primary-200 font-semibold cursor-pointer transition-all hover:border-primary-500"
                    >
                        <span class="text-lg leading-none">+</span>
                        @lang('passport::app.mapping.add-field')
                    </button>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-passport-source-select', {
                template: '#v-passport-source-select-template',

                props: {
                    value: { type: String, default: '' },
                    initialLabel: { type: String, default: '' },
                    placeholder: { type: String, default: '' },
                    queryParams: { type: Object, default: () => ({}) },
                },

                emits: ['input'],

                data() {
                    return {
                        options: [],
                        isLoading: false,
                        timer: null,
                        selected: this.value
                            ? { code: this.value, label: this.initialLabel || this.value }
                            : null,
                    };
                },

                watch: {
                    selected(option) {
                        this.$emit('input', option?.code ?? '');
                    },
                },

                mounted() {
                    this.fetch('');
                },

                methods: {
                    {{-- Searching server-side keeps the page independent of catalog size:
                         a large catalog holds tens of thousands of attributes. --}}
                    search(query) {
                        clearTimeout(this.timer);

                        this.timer = setTimeout(() => this.fetch(query), 500);
                    },

                    fetch(query) {
                        this.isLoading = true;

                        this.$axios.get("{{ route('admin.catalog.options.fetch-all') }}", {
                            params: {
                                entityName: 'attributes',
                                locale: "{{ core()->getRequestedLocaleCode() }}",
                                query,
                                ...this.queryParams,
                            },
                        })
                            .then(({ data }) => {
                                this.options = data.options.map((option) => ({
                                    code: option.code,
                                    label: option.label,
                                }));
                            })
                            .finally(() => this.isLoading = false);
                    },
                },
            });

            app.component('v-passport-custom-fields', {
                template: '#v-passport-custom-fields-template',

                props: {
                    initial: { type: [String, Array], default: () => [] },
                    sourceParams: { type: [String, Object], default: () => ({}) },
                },

                data() {
                    return {
                        rows: [],
                        sequence: 0,
                        namePlaceholder: @json(trans('passport::app.mapping.custom-name-placeholder')),
                        sourcePlaceholder: @json(trans('passport::app.mapping.select-source')),
                        removeTitle: @json(trans('passport::app.mapping.custom-remove')),
                    };
                },

                computed: {
                    sourceParamsObject() {
                        return this.decode(this.sourceParams, {});
                    },
                },

                mounted() {
                    const saved = this.decode(this.initial, []);

                    this.rows = Array.isArray(saved)
                        ? saved.map((row, index) => ({
                            uid: index,
                            name: row.name ?? '',
                            attribute: row.attribute ?? '',
                            label: row.label ?? '',
                        }))
                        : [];
                },

                methods: {
                    decode(value, fallback) {
                        if (value && typeof value === 'object') {
                            return value;
                        }

                        try {
                            let parsed = JSON.parse(value);

                            if (typeof parsed === 'string') {
                                parsed = JSON.parse(parsed);
                            }

                            return parsed ?? fallback;
                        } catch (exception) {
                            return fallback;
                        }
                    },

                    addRow() {
                        this.rows.push({ uid: `new-${this.sequence++}`, name: '', attribute: '', label: '' });
                    },

                    removeRow(index) {
                        this.rows.splice(index, 1);
                    },

                    setAttribute(index, code) {
                        this.rows[index].attribute = code ?? '';
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>

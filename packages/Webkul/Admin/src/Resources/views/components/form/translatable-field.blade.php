@props([
    'locales'       => [],
    'values'        => [],
    'field'         => 'name',
    'nameTemplate'  => ':locale[:field]',
    'label'         => '',
    'placeholder'   => '',
    'currentLocale' => null,
    /**
     * Set false when the field edits a draft (a repeater row in a modal) rather
     * than the form itself: the hidden per-locale inputs are skipped and every
     * edit is emitted as `update:values` for the caller to store instead.
     */
    'submit' => true,
])

@php
    $localeOptions = collect($locales)->map(fn ($locale) => [
        'id'    => $locale->code,
        'label' => $locale->name ? $locale->name.' ('.$locale->code.')' : $locale->code,
    ])->values();

    $localeValues = collect($locales)->mapWithKeys(fn ($locale) => [
        $locale->code => $values[$locale->code] ?? '',
    ]);

    /**
     * A caller may bind the values reactively (`::values="draft"`). That binding
     * replaces the rendered one instead of being appended, which would leave the
     * tag with two `:values` attributes and fail Vue's template compiler.
     */
    $valuesExpression = $attributes->get(':values') ?: $localeValues->toJson();

    $attributes = $attributes->except([':values']);
@endphp

<v-translatable-field
    :locales='@json($localeOptions)'
    :values='{!! $valuesExpression !!}'
    field="{{ $field }}"
    name-template="{{ $nameTemplate }}"
    label="{{ $label }}"
    placeholder="{{ $placeholder }}"
    current="{{ $currentLocale ?? core()->getRequestedLocaleCode() }}"
    submit="{{ filter_var($submit, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false' }}"
    {{ $attributes }}
></v-translatable-field>

@if (filter_var($submit, FILTER_VALIDATE_BOOLEAN))
    @foreach ($locales as $locale)
        <x-admin::form.control-group.error :control-name="str_replace([':locale', ':field'], [$locale->code, $field], $nameTemplate)" />
    @endforeach
@endif

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-translatable-field-template"
    >
        <x-admin::form.control-group>
            <div class="flex gap-2 items-center mb-1.5 text-xs text-gray-800 dark:text-white font-medium">
                <span class="inline-flex items-center gap-1">
                    <span v-text="label"></span>

                    <span class="unsaved-badge hidden">@lang('admin::app.components.form.unsaved-changes.field-badge')</span>
                </span>

                <span
                    class="ltr:ml-auto rtl:mr-auto rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-600 dark:bg-cherry-900 dark:text-gray-300"
                    v-text="summary"
                ></span>

                <x-admin::form.switcher
                    ::items="locales"
                    ::model-value="selected"
                    ::marked="translatedLocales"
                    @update:model-value="selected = $event"
                />
            </div>

            {{--
                A plain input rather than `x-admin::form.control-group.control`: that
                control registers a named vee-validate field, which would submit the
                switcher's working value and mark the form dirty on every locale
                change. Each locale's value travels in the hidden inputs below, which
                stay in this control group so the unsaved-changes tracker marks them
                touched when the field is edited.
            --}}
            <input
                type="text"
                class="w-full py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-cherry-900 dark:hover:border-slate-300 dark:border-gray-600"
                v-model="localValues[selected]"
                :placeholder="placeholder"
            />

            <input
                v-if="submits"
                type="hidden"
                v-for="locale in locales"
                :key="'translatable-' + field + '-' + locale.id"
                :name="nameFor(locale.id)"
                :value="localValues[locale.id]"
            />
        </x-admin::form.control-group>
    </script>

    <script type="module">
        app.component('v-translatable-field', {
            template: '#v-translatable-field-template',

            props: {
                locales: {
                    type: Array,
                    default: () => [],
                },

                values: {
                    type: Object,
                    default: () => ({}),
                },

                field: {
                    type: String,
                    default: 'name',
                },

                nameTemplate: {
                    type: String,
                    default: ':locale[:field]',
                },

                label: {
                    type: String,
                    default: '',
                },

                placeholder: {
                    type: String,
                    default: '',
                },

                current: {
                    type: String,
                    default: '',
                },

                submit: {
                    type: [Boolean, String],
                    default: true,
                },
            },

            emits: ['update:values'],

            data() {
                return {
                    localValues: Object.assign({}, this.values),
                    selected: this.values.hasOwnProperty(this.current)
                        ? this.current
                        : (this.locales[0]?.id ?? ''),
                    summaryText: @json(trans('admin::app.components.form.translatable-field.translated-count')),
                };
            },

            methods: {
                nameFor(locale) {
                    return this.nameTemplate.replace(':locale', locale).replace(':field', this.field);
                },
            },

            watch: {
                localValues: {
                    deep: true,
                    handler(values) {
                        this.$emit('update:values', { ...values });
                    },
                },
            },

            computed: {
                submits() {
                    return this.submit !== false && this.submit !== 'false';
                },

                translatedLocales() {
                    return this.locales
                        .filter(locale => String(this.localValues[locale.id] ?? '').trim() !== '')
                        .map(locale => locale.id);
                },

                summary() {
                    return this.summaryText
                        .replace(':filled', this.translatedLocales.length)
                        .replace(':total', this.locales.length);
                },
            },
        });
    </script>
@endPushOnce

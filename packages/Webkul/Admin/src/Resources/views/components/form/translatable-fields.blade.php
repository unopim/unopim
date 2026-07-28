@props([
    'locales' => [],
    'label'   => '',
])

@php
    $localeOptions = collect($locales)->map(fn ($locale) => [
        'id'    => $locale->code,
        'label' => $locale->name ? $locale->name.' ('.$locale->code.')' : $locale->code,
    ])->values();
@endphp

<v-translatable-fields
    :locales='@json($localeOptions)'
    label="{{ $label }}"
    current="{{ core()->getRequestedLocaleCode() }}"
    {{ $attributes }}
>
    <template v-slot:default="{ locale }">
        {{ $slot }}
    </template>
</v-translatable-fields>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-translatable-fields-template"
    >
        <div>
            <div class="flex gap-2 items-center mb-1.5 text-xs text-gray-800 dark:text-white font-medium">
                <span class="inline-flex items-center gap-1">
                    <span v-text="label"></span>

                    <span class="unsaved-badge hidden">@lang('admin::app.components.form.unsaved-changes.field-badge')</span>
                </span>

                <x-admin::form.switcher
                    class="ltr:ml-auto rtl:mr-auto"
                    ::items="locales"
                    ::model-value="selected"
                    @update:model-value="selected = $event"
                />
            </div>

            <slot :locale="selected"></slot>
        </div>
    </script>

    <script type="module">
        app.component('v-translatable-fields', {
            template: '#v-translatable-fields-template',

            props: {
                locales: {
                    type: Array,
                    default: () => [],
                },

                label: {
                    type: String,
                    default: '',
                },

                current: {
                    type: String,
                    default: '',
                },
            },

            data() {
                return {
                    selected: this.locales.some(locale => locale.id === this.current)
                        ? this.current
                        : (this.locales[0]?.id ?? ''),
                };
            },
        });
    </script>
@endPushOnce

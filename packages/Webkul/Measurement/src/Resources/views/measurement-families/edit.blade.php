<x-admin::layouts.with-history>
    <x-slot:entityName>
        Measurement Family
    </x-slot:entityName>

    <x-slot:title>
        @lang('measurement::app.measurement.edit.measurement_edit')
    </x-slot:title>

    <x-admin::form
        method="PUT"
        action="{{ route('admin.measurement.families.update', $family->id) }}"
    >
        <div class="grid gap-2.5">
            <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                <div class="grid gap-1.5">
                    <p class="text-xl font-bold leading-6 text-gray-800 dark:text-slate-50">
                        @lang('measurement::app.measurement.edit.measurement_edit')
                        | Name: {{ $family->name }}
                    </p>
                </div>

                <div class="flex items-center gap-x-2.5">
                    <a
                        href="{{ route('admin.measurement.families.index') }}"
                        class="transparent-button"
                    >
                        @lang('measurement::app.measurement.edit.back')
                    </a>

                    @if (bouncer()->hasPermission('catalog.measurements.families.edit'))
                        <button class="primary-button">
                            @lang('measurement::app.measurement.edit.save')
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="left-column flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="relative rounded bg-white p-4 box-shadow dark:bg-cherry-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('measurement::app.measurement.edit.general')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('measurement::app.measurement.edit.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="code"
                            value="{{ old('code', $family->code) }}"
                            rules="required"
                            placeholder="{{ __('Enter family name') }}"
                            disabled
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <div>
                        <div class="flex items-center justify-between p-1.5">
                            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('measurement::app.measurement.edit.label')
                            </p>
                        </div>

                        @foreach ($locales as $locale)
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    {{ $locale->name }}
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="labels[{{ $locale->code }}]"
                                    value="{{ old('labels.' . $locale->code, $labels[$locale->code] ?? '') }}"
                                />
                            </x-admin::form.control-group>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-admin::form>

    <div class="mt-4 rounded bg-white p-4 box-shadow dark:bg-cherry-900">
        <v-locales>
            <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                <p class="text-xl font-bold text-gray-800 dark:text-slate-50">
                    @lang('measurement::app.measurement.edit.units')
                </p>

                <div class="flex items-center gap-x-2.5">
                    @if (bouncer()->hasPermission('catalog.measurements.units.create'))
                        <button
                            type="button"
                            class="primary-button"
                        >
                            @lang('measurement::app.measurement.edit.create_units')
                        </button>
                    @endif
                </div>
            </div>

            <x-admin::shimmer.datagrid />
        </v-locales>
    </div>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-locales-template">
            <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
                <p class="text-xl font-bold text-gray-800 dark:text-slate-50">
                    @lang('measurement::app.measurement.edit.units')
                </p>

                <div class="flex items-center gap-x-2.5">
                    @if (bouncer()->hasPermission('catalog.measurements.units.create'))
                        <button
                            type="button"
                            class="primary-button"
                            @click="selectedLocales = 0; resetForm(); $refs.localeUpdateOrCreateModal.toggle()"
                        >
                            @lang('measurement::app.measurement.edit.create_units')
                        </button>
                    @endif
                </div>
            </div>

            @php
                $hasDeletePermission = bouncer()->hasPermission('catalog.measurements.units.delete');
                $hasEditPermission = bouncer()->hasPermission('catalog.measurements.units.edit');
            @endphp

            <x-admin::datagrid
                :src="route('admin.measurement.families.units', $family->id)"
                ref="datagrid"
            >
                <template #body="{ columns, records, performAction, applied, setCurrentSelectionMode }">
                    <div
                        v-for="record in records"
                        class="row grid items-center gap-2.5 border-b px-4 py-4 text-gray-600 transition-all hover:bg-violet-50 dark:border-cherry-800 dark:text-gray-300 dark:hover:bg-cherry-800"
                        :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                    >
                        <p v-text="record.code"></p>

                        <p v-text="record.label"></p>

                        <p v-html="record.is_standard"></p>

                        <div class="flex justify-end">
                            @if ($hasEditPermission)
                                <a
                                    v-if="record.actions.find(a => a.index === 'edit' && a.url)"
                                    @click="selectedLocales = 1; editModal(record.actions.find(a => a.index === 'edit').url)"
                                >
                                    <span
                                        class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800"
                                        title="Edit"
                                    ></span>
                                </a>
                            @endif

                            @if ($hasDeletePermission)
                                <a
                                    v-if="record.actions.find(a => a.index === 'delete' && a.url)"
                                    @click="performAction(record.actions.find(a => a.index === 'delete'))"
                                >
                                    <span
                                        class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800"
                                        title="Delete"
                                    ></span>
                                </a>
                            @endif
                        </div>
                    </div>
                </template>
            </x-admin::datagrid>

            @if (bouncer()->hasPermission('catalog.measurements.units.create') || bouncer()->hasPermission('catalog.measurements.units.edit'))
            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    ref="createLocaleForm"
                    @submit="handleSubmit($event, updateOrCreate)"
                >
                    <x-admin::modal ref="localeUpdateOrCreateModal">
                        <x-slot:header>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">
                                <span v-if="selectedLocales">
                                    @lang('measurement::app.measurement.unit.edit_unit')
                                </span>

                                <span v-else>
                                    @lang('measurement::app.measurement.unit.create_unit')
                                </span>
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group.control
                                type="hidden"
                                name="id"
                                v-model="locale.id"
                            />

                            <div class="grid grid-cols-3 gap-4">
                                <x-admin::form.control-group class="mb-0">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.settings.locales.index.create.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="code"
                                        name="code"
                                        rules="required"
                                        v-model="locale.code"
                                        :label="trans('admin::app.settings.locales.index.create.code')"
                                        :placeholder="trans('admin::app.settings.locales.index.create.code')"
                                        ::readonly="selectedLocales === 1"
                                    />

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group class="mb-0">
                                        <x-admin::form.control-group.label>
                                            {{ $locale->name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            id="label"
                                            ::name="`labels[{{ $locale->code }}]`"
                                            v-model="locale.labels['{{ $locale->code }}']"
                                        />
                                    </x-admin::form.control-group>
                                @endforeach

                                <x-admin::form.control-group class="mb-0">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('measurement::app.measurement.unit.symbol')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="symbol"
                                        name="symbol"
                                        rules="required"
                                        v-model="locale.symbol"
                                        placeholder="Symbol"
                                    />

                                    <x-admin::form.control-group.error control-name="symbol" />
                                </x-admin::form.control-group>
                            </div>

                            <div class="mt-4">
                                <x-admin::form.control-group class="mb-0">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('measurement::app.measurement.unit.conversion_operation')
                                    </x-admin::form.control-group.label>
                                </x-admin::form.control-group>

                                <div
                                    v-for="(conversion, index) in locale.conversions"
                                    :key="index"
                                    class="flex items-start gap-2"
                                >
                                    <x-admin::form.control-group class="mb-2 w-1/3">
                                        <x-admin::form.control-group.control
                                            type="number"
                                            ::name="'convert_value[' + index + ']'"
                                            rules="required"
                                            step="0.000001"
                                            v-model="conversion.value"
                                            placeholder="Enter conversion value"
                                            ::disabled="isConversionDisabled"
                                            :label="trans('measurement::app.measurement.unit.conversion_value')"
                                        />

                                        <v-error-message
                                            :name="'convert_value[' + index + ']'"
                                            v-slot="{ message }"
                                        >
                                            <p
                                                class="mt-1 text-xs italic text-red-600"
                                                v-text="message"
                                            ></p>
                                        </v-error-message>
                                    </x-admin::form.control-group>

                                    <x-admin::form.control-group class="mb-2 flex-1">
                                        <x-admin::form.control-group.control
                                            type="select"
                                            ::name="'convert_from_standard[' + index + ']'"
                                            rules="required"
                                            v-model="conversion.operator"
                                            :options="json_encode($operationOptions)"
                                            track-by="value"
                                            label-by="label"
                                            ::disabled="isConversionDisabled"
                                            :label="trans('measurement::app.measurement.unit.conversion_operator')"
                                        />

                                        <v-error-message
                                            :name="'convert_from_standard[' + index + ']'"
                                            v-slot="{ message }"
                                        >
                                            <p
                                                class="mt-1 text-xs italic text-red-600"
                                                v-text="message"
                                            ></p>
                                        </v-error-message>
                                    </x-admin::form.control-group>

                                    <button
                                        type="button"
                                        class="mt-1.5 flex items-center justify-center rounded"
                                        @click="removeConversion(index)"
                                        :disabled="locale.conversions.length === 1 || isConversionDisabled"
                                    >
                                        <span
                                            class="icon-delete cursor-pointer rounded-md text-2xl transition-all hover:bg-violet-100 dark:hover:bg-gray-800"
                                            title="Delete"
                                        ></span>
                                    </button>
                                </div>

                                <div class="mt-2">
                                    <button
                                        type="button"
                                        class="secondary-button"
                                        @click="addConversion"
                                        :disabled="locale.conversions.length >= 5 || isConversionDisabled"
                                    >
                                        @lang('measurement::app.measurement.unit.add_new_operation')
                                    </button>
                                </div>
                            </div>
                        </x-slot>

                        <x-slot:footer>
                            <div class="flex items-center gap-x-2.5">
                                <button
                                    type="submit"
                                    class="primary-button"
                                >
                                    @lang('measurement::app.measurement.unit.save')
                                </button>
                            </div>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
            @endif
        </script>

        <script type="module">
            app.component('v-locales', {
                template: '#v-locales-template',

                data() {
                    return {
                        locale: {
                            id: null,
                            code: null,
                            name: null,
                            labels: {},
                            status: false,
                            conversions: [
                                {
                                    operator: 'mul',
                                    value: null,
                                },
                            ],
                        },

                        familyUsedInProducts: Boolean(
                            {{ json_encode($familyUsedInProducts ?? false) }}
                        ),

                        selectedLocales: 0,
                    };
                },

                computed: {
                    gridsCount() {
                        let count = this.$refs.datagrid.available.columns.length;

                        if (this.$refs.datagrid.available.actions.length) {
                            ++count;
                        }

                        if (this.$refs.datagrid.available.massActions.length) {
                            ++count;
                        }

                        return count;
                    },

                    isConversionDisabled() {
                        if (this.selectedLocales === 0) {
                            return false;
                        }

                        return this.familyUsedInProducts
                            || this.locale.is_used_in_products
                            || this.locale.is_standard;
                    },
                },

                methods: {
                    updateOrCreate(params, { resetForm, setErrors }) {
                        let formData = new FormData(this.$refs.createLocaleForm);

                        let url = "{{ route('admin.measurement.families.units.store', $family->id) }}";

                        if (this.selectedLocales && this.locale.code) {
                            url = "{{ route('admin.measurement.families.units.update', ['familyId' => $family->id, 'code' => '__CODE__']) }}"
                                .replace('__CODE__', this.locale.code);

                            formData.append('_method', 'PUT');
                        }

                        this.$axios.post(url, formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data',
                            },
                        })
                        .then((response) => {
                            this.$refs.localeUpdateOrCreateModal.close();

                            this.$emitter.emit('add-flash', {
                                type: 'success',
                                message: 'Unit saved successfully',
                            });

                            this.$refs.datagrid.get();

                            resetForm();
                        })
                        .catch((error) => {
                            if (error.response?.status === 422) {
                                setErrors(error.response.data.errors);
                            } else if (error.response?.status === 403) {
                                this.$refs.localeUpdateOrCreateModal.close();

                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response.data?.message
                                        || "@lang('measurement::app.acl.unauthorized')",
                                });
                            } else {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message
                                        || "@lang('measurement::app.acl.unauthorized')",
                                });
                            }
                        });
                    },

                    editModal(url) {
                        this.$axios.get(url).then((response) => {
                            let conversions = response.data.data.convert_from_standard ?? [];

                            if (! Array.isArray(conversions)) {
                                conversions = [
                                    {
                                        operator: response.data.data.convert_from_standard ?? 'mul',
                                        value: response.data.data.convert_value ?? null,
                                    },
                                ];
                            }

                            this.locale = {
                                code: response.data.data.code,
                                labels: response.data.data.labels ?? {},
                                symbol: response.data.data.symbol ?? null,
                                is_standard: response.data.data.is_standard ?? 0,
                                is_used_in_products: response.data.data.is_used_in_products ?? false,

                                conversions: conversions.length
                                    ? conversions.map((conversion) => ({
                                        operator: conversion.operator ?? 'mul',
                                        value: conversion.value ?? null,
                                    }))
                                    : [
                                        {
                                            operator: 'mul',
                                            value: null,
                                        },
                                    ],
                            };

                            this.selectedLocales = 1;

                            this.$refs.localeUpdateOrCreateModal.toggle();
                        });
                    },

                    addConversion() {
                        if (this.locale.conversions.length >= 5) {
                            return;
                        }

                        this.locale.conversions.push({
                            operator: 'mul',
                            value: null,
                        });
                    },

                    removeConversion(index) {
                        if (this.locale.conversions.length === 1) {
                            return;
                        }

                        this.locale.conversions.splice(index, 1);
                    },

                    resetForm() {
                        this.locale = {
                            code: null,
                            labels: {},
                            symbol: null,
                            is_used_in_products: false,

                            conversions: [
                                {
                                    operator: 'mul',
                                    value: null,
                                },
                            ],
                        };

                        this.selectedLocales = 0;
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts.with-history>
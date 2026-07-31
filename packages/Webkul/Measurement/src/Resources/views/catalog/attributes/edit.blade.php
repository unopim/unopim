@if ($attribute && $attribute->type === 'measurement')
    @php
        $measurementData = app(\Webkul\Measurement\Services\AttributeMeasurementService::class)
            ->buildPayload($attribute->id);
    @endphp

    <v-measurement
        :attribute-id="{{ $attribute->id }}"
        measurement-url="{{ route('measurement.attribute', ['attributeId' => $attribute->id]) }}"
        family-units-url="{{ route('admin.measurement.family.units') }}"
        :initial-data='@json($measurementData)'
    >
    </v-measurement>
@endif

@pushOnce('scripts')
    <script type="text/x-template" id="v-measurement-template">
        <div class="mt-4 rounded bg-white p-4 shadow-sm dark:bg-cherry-900">
            <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                @lang('measurement::app.attribute_type.measurement_families')
            </p>

            <x-admin::form.control-group v-if="familyOptions">
                <x-admin::form.control-group.label class="required">
                    @lang('measurement::app.attribute_type.measurement_family')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="measurement_family"
                    id="measurement_family"
                    ::options="familyOptions"
                    v-model="oldFamily"
                    ::value="oldFamily"
                    rules="required"
                    track-by="id"
                    label-by="label"
                    :placeholder="trans('measurement::app.measurement.attribute.select_family')"
                    ::disabled="isSavedFamily"
                />

                <x-admin::form.control-group.error control-name="measurement_family" />
            </x-admin::form.control-group>

            <x-admin::form.control-group
                class="mt-4"
                v-if="unitsList"
            >
                <x-admin::form.control-group.label class="required">
                    @lang('measurement::app.attribute_type.measurement_unit')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="measurement_unit"
                    id="measurement_unit"
                    ::options="unitsList"
                    v-model="oldUnit"
                    ::value="oldUnit"
                    rules="required"
                    track-by="id"
                    label-by="label"
                    :placeholder="trans('measurement::app.measurement.attribute.select_unit')"
                />

                <x-admin::form.control-group.error control-name="measurement_unit" />
            </x-admin::form.control-group>
        </div>
    </script>

    <script type="module">
        app.component('v-measurement', {
            template: '#v-measurement-template',

            props: [
                'attributeId',
                'measurementUrl',
                'familyUnitsUrl',
                'initialData',
            ],

            data() {
                return {
                    familyOptions: null,
                    measurementFamily: null,
                    measurementUnit: null,
                    unitsList: null,
                    oldFamily: null,
                    oldUnit: null,
                    isInitialLoad: true,
                    isSavedFamily: false,
                    unitsCache: {},
                };
            },

            created() {
                if (this.initialData) {
                    this.applyData(this.initialData);
                }
            },

            async mounted() {
                if (this.familyOptions !== null) {
                    return;
                }

                try {
                    const response = await this.$axios.get(this.measurementUrl);

                    this.applyData(response.data);
                } catch (error) {
                    console.error('Error loading measurement data:', error);
                }
            },

            methods: {
                applyData(data) {
                    this.familyOptions = data.familyOptions || [];
                    this.oldFamily = data.oldFamily;
                    this.oldUnit = data.oldUnit;

                    if (this.oldFamily) {
                        this.isSavedFamily = true;
                    }

                    if (this.oldFamily && this.familyOptions.length > 0) {
                        const family = this.familyOptions.find((family) =>
                            family.id.toString().toLowerCase()
                            === this.oldFamily.toString().toLowerCase()
                        );

                        if (family) {
                            this.measurementFamily = JSON.stringify(family);

                            this.unitsList = data.units || [];

                            if (this.oldUnit) {
                                const oldUnitObj = this.unitsList.find(
                                    (unit) => unit.id === this.oldUnit
                                );

                                if (oldUnitObj) {
                                    this.$nextTick(() => {
                                        this.measurementUnit = JSON.stringify(oldUnitObj);
                                    });
                                }
                            }
                        }
                    }

                    this.isInitialLoad = false;
                },

                /**
                 * Units belong to one family and are fetched per selection, so the
                 * page does not carry the units of every family in the catalogue.
                 */
                async fetchUnits(familyCode) {
                    if (! familyCode) {
                        this.unitsList = [];

                        return;
                    }

                    if (this.unitsCache[familyCode]) {
                        this.unitsList = this.unitsCache[familyCode];

                        return;
                    }

                    try {
                        const response = await this.$axios.get(this.familyUnitsUrl, {
                            params: { family: familyCode },
                        });

                        this.unitsCache[familyCode] = response.data.units || [];
                        this.unitsList = this.unitsCache[familyCode];
                    } catch (error) {
                        console.error('Error loading measurement units:', error);

                        this.unitsList = [];
                    }
                },
            },

            watch: {
                oldFamily(newValue) {
                    let selectedFamily = null;

                    if (
                        typeof newValue === 'string'
                        && newValue.trim() !== ''
                    ) {
                        try {
                            selectedFamily = JSON.parse(newValue);
                        } catch (e) {
                            return;
                        }
                    } else if (
                        newValue
                        && typeof newValue === 'object'
                    ) {
                        selectedFamily = newValue;
                    }

                    if (this.isInitialLoad) {
                        return;
                    }

                    this.measurementUnit = null;

                    this.fetchUnits(selectedFamily ? selectedFamily.id : null);
                },
            },
        });
    </script>
@endPushOnce
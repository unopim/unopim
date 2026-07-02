@if ($attribute && $attribute->type === 'measurement')
    @php
        $measurementData = app(\Webkul\Measurement\Services\AttributeMeasurementService::class)
            ->buildPayload($attribute->id);
    @endphp

    <v-measurement
        :attribute-id="{{ $attribute->id }}"
        measurement-url="{{ route('measurement.attribute', ['attributeId' => $attribute->id]) }}"
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
                    name="measurement family"
                    id="measurement family"
                    ::options="familyOptions"
                    v-model="oldFamily"
                    ::value="oldFamily"
                    rules="required"
                    track-by="id"
                    label-by="label"
                    placeholder="Select Family"
                    ::disabled="isSavedFamily"
                />

                <x-admin::form.control-group.error control-name="measurement family" />
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
                    name="measurement unit"
                    id="measurement unit"
                    ::options="unitsList"
                    v-model="oldUnit"
                    ::value="oldUnit"
                    rules="required"
                    track-by="id"
                    label-by="label"
                    placeholder="Select Unit"
                />

                <x-admin::form.control-group.error control-name="measurement unit" />
            </x-admin::form.control-group>
        </div>
    </script>

    <script type="module">
        app.component('v-measurement', {
            template: '#v-measurement-template',

            props: [
                'attributeId',
                'measurementUrl',
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
                    const response = await axios.get(this.measurementUrl);

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

                            this.unitsList = family.units || [];

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

                    this.unitsList = selectedFamily
                        ? (selectedFamily.units || [])
                        : [];

                    if (! this.isInitialLoad) {
                        this.measurementUnit = null;
                    }
                },
            },
        });
    </script>
@endPushOnce
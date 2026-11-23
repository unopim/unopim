@php
    $measurementBackedTypes = collect(config('attribute_types'))
        ->filter(fn (array $type): bool => ! empty($type['measurement_backed']))
        ->keys()
        ->values()
        ->toArray();

    $familyOptions = app(\Webkul\Measurement\Services\AttributeMeasurementService::class)->familyOptions();
@endphp

<v-measurement-create
    :selected-type="selectedType"
    :measurement-types='@json($measurementBackedTypes)'
    :family-options='@json($familyOptions)'
    family-units-url="{{ route('admin.measurement.family.units') }}"
></v-measurement-create>

@pushOnce('scripts')
    <script type="text/x-template" id="v-measurement-create-template">
        <div v-if="isMeasurement">
            <x-admin::form.control-group v-if="familyOptions && familyOptions.length">
                <x-admin::form.control-group.label class="required">
                    @lang('measurement::app.attribute_type.measurement_family')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="measurement_family"
                    id="measurement_family"
                    ::options="familyOptions"
                    v-model="family"
                    rules="required"
                    track-by="id"
                    label-by="label"
                    :placeholder="trans('measurement::app.measurement.attribute.select_family')"
                />

                <x-admin::form.control-group.error control-name="measurement_family" />
            </x-admin::form.control-group>

            <x-admin::form.control-group v-if="unitsList && unitsList.length">
                <x-admin::form.control-group.label class="required">
                    @lang('measurement::app.attribute_type.measurement_unit')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="select"
                    name="measurement_unit"
                    id="measurement_unit"
                    ::options="unitsList"
                    v-model="unit"
                    rules="required"
                    track-by="id"
                    label-by="label"
                    :placeholder="trans('measurement::app.measurement.attribute.select_unit')"
                />

                <x-admin::form.control-group.error control-name="measurement_unit" />
            </x-admin::form.control-group>
        </div>
    </script>

    @include('measurement::catalog.attributes._units-mixin')

    <script type="module">
        app.component('v-measurement-create', {
            template: '#v-measurement-create-template',

            mixins: [window.measurementUnitsMixin],

            props: [
                'selectedType',
                'measurementTypes',
                'familyOptions',
                'familyUnitsUrl',
            ],

            data() {
                return {
                    family: null,
                    unit: null,
                };
            },

            computed: {
                isMeasurement() {
                    return (this.measurementTypes || []).includes(this.selectedType);
                },
            },

            watch: {
                selectedType() {
                    if (! this.isMeasurement) {
                        this.family = null;
                        this.unit = null;
                        this.unitsList = null;
                    }
                },

                family(newValue) {
                    const selectedFamily = this.parseSelectedFamily(newValue);

                    if (selectedFamily === undefined) {
                        return;
                    }

                    this.unit = null;

                    this.fetchUnits(selectedFamily ? selectedFamily.id : null);
                },
            },
        });
    </script>
@endPushOnce

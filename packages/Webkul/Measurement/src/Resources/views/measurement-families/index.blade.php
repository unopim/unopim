<x-admin::layouts>
    <x-slot:title>
        {{ __('Measurement Families') }}
    </x-slot>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap mb-4">
        <p class="text-xl text-gray-800 dark:text-white font-bold">            
            @lang('measurement::app.attribute_type.measurement_families')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <v-create-family-form />
        </div>
    </div>

    <x-admin::datagrid
        ref="datagrid"
        src="{{ route('admin.measurement.families.index') }}">
    </x-admin::datagrid>

    @pushOnce('scripts')

    <script type="text/x-template" id="v-create-family-form-template">
        <div>
            <button
                type="button"
                class="primary-button"
                @click="$refs.familyCreateModal.toggle()"
            >
                @lang('measurement::app.measurement.index.create')
            </button>

            <x-admin::form v-slot="{ errors, handleSubmit }" as="div">
            <form @submit="handleSubmit($event, save)">
            <x-admin::modal ref="familyCreateModal">

                <x-slot:header>
                    <h2 class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('measurement::app.measurement.index.create')
                    </h2>
                </x-slot:header>

                <x-slot:content>

                            <div class="space-y-3">

                                <!-- Code -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('measurement::app.measurement.index.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="code"
                                        v-model="form.code"
                                        rules="required"
                                        label="Code"
                                        placeholder="Enter family code"
                                    />

                                    <x-admin::form.control-group.error control-name="code" />
                                </x-admin::form.control-group>

                                <!-- Standard Unit Code -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('measurement::app.measurement.index.standard')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="standard_unit_code"
                                        v-model="form.standard_unit_code"
                                        rules="required"
                                        label="Standard Unit Code"
                                        placeholder="Enter standard unit code"
                                    />

                                    <x-admin::form.control-group.error control-name="standard_unit_code" />
                                </x-admin::form.control-group>

                                <!-- Symbol -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label>
                                        @lang('measurement::app.measurement.index.symbol')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        name="symbol"
                                        v-model="form.symbol"
                                        placeholder="e.g. km, m"
                                    />
                                </x-admin::form.control-group>

                                <!-- Labels -->
                                <div>
                                    <p class="text-gray-800 dark:text-white text-sm font-semibold mb-2">
                                        @lang('admin::app.catalog.attributes.create.label')
                                    </p>
                                </div>

                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            {{ $locale->name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            :name="'labels[{{ $locale->code }}]'"
                                            v-model="form.labels['{{ $locale->code }}']"
                                            placeholder="Enter label"
                                        />
                                    </x-admin::form.control-group>
                                @endforeach

                            </div>
                </x-slot:content>
                <x-slot:footer>
                    <div class="flex gap-x-2.5 items-center">
                        <button
                        type="submit"
                        class="primary-button"
                        >
                            @lang('measurement::app.measurement.index.save')
                        </button>
                    </div>
                </x-slot>
                        

            </x-admin::modal>
            </form>
            </x-admin::form>

        </div>
    </script>

    <script type="module">
        app.component('v-create-family-form', {

            template: '#v-create-family-form-template',

            data() {
                return {
                    form: {
                        code: '',
                        standard_unit_code: '',
                        symbol: '',
                        labels: {}
                    }
                };
            },

            methods: {

                save() {

                    axios.post(
                        "{{ route('admin.measurement.families.store') }}",
                        this.form
                    )
                    .then(response => {

                        this.$refs.familyCreateModal.close();

                        this.form = {
                            code: '',
                            standard_unit_code: '',
                            symbol: '',
                            labels: {}
                        };

                        if (response.data?.data?.redirect_url) {
                                window.location.href = response.data.data.redirect_url;
                        }

                    })
                    .catch(error => {
                        console.error(error);
                    });

                }

            }

        });
    </script>

    @endPushOnce

</x-admin::layouts>
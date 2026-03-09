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

                
                <x-admin::modal ref="familyCreateModal">
                    <x-slot:header>
                        <h2 class="text-base text-gray-800 dark:text-white font-semibold">
                            @lang('measurement::app.measurement.index.create')
                        </h2>
                    </x-slot:header>

                    <x-slot:content>
                       
                        <div class=""> 
                            <div class="mb-3">
                               
                            </div>

                            <div class="space-y-2"> <x-admin::form.control-group class="!mb-1">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('measurement::app.measurement.index.code')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        v-model="form.code"
                                        placeholder="{{ __('Enter family code') }}"
                                    />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="!mb-1">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('measurement::app.measurement.index.standard')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        v-model="form.standard_unit_code"
                                        placeholder="{{ __('Enter standard unit code') }}"
                                    />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="!mb-1">
                                    <x-admin::form.control-group.label>
                                       @lang('measurement::app.measurement.index.symbol')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        v-model="form.symbol"
                                        placeholder="{{ __('e.g. km, m') }}"
                                    />
                                </x-admin::form.control-group>

                                <div class="">
                                    <p class="text-gray-800 dark:text-white text-sm font-semibold mb-2">
                                        @lang('admin::app.catalog.attributes.create.label')
                                    </p>
                                </div>

                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group class="!mb-0">
                                        <x-admin::form.control-group.label>
                                            {{ $locale->name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            v-model="form.labels['{{ $locale->code }}']"
                                            placeholder="{{ __('Enter label') }}"
                                        />
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </div>
                    </x-slot:content>

                    <x-slot:footer>
                        <button
                            type="button"
                            class="primary-button"
                            @click="save"
                        >
                             @lang('measurement::app.measurement.index.save')
                        </button>
                    </x-slot:footer>
                </x-admin::modal>
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

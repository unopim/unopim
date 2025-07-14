<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.products.index.title')
        </x-slot>

        <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.catalog.products.index.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <!-- Export Modal -->
                <x-admin::datagrid.export src="{{ route('admin.catalog.products.index') }}" />

                {!! view_render_event('unopim.admin.catalog.products.create.before') !!}

                @if (bouncer()->hasPermission('catalog.products.create'))
                <v-create-product-form>
                    <button
                        type="button"
                        class="primary-button">
                        @lang('admin::app.catalog.products.index.create-btn')
                    </button>
                </v-create-product-form>
                @endif

                {!! view_render_event('unopim.admin.catalog.products.create.after') !!}
            </div>
        </div>

        {!! view_render_event('unopim.admin.catalog.products.list.before') !!}

    <!-- Datagrid -->
    <x-admin::datagrid src="{{ route('admin.catalog.products.index') }}" :isMultiRow="true">

    </x-admin::datagrid>

        {!! view_render_event('unopim.admin.catalog.products.list.after') !!}

        @pushOnce('scripts')
        <script type="text/x-template" id="v-create-product-form-template">
            <div>
                <!-- Product Create Button -->
                @if (bouncer()->hasPermission('catalog.products.create'))
                    <button
                        type="button"
                        class="primary-button"
                        @click="$refs.productCreateModal.toggle()"
                    >
                        @lang('admin::app.catalog.products.index.create-btn')
                    </button>
                @endif

                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form @submit="handleSubmit($event, create)" ref="productCreateForm">
                        <!-- Customer Create Modal -->
                        <x-admin::modal ref="productCreateModal">
                            <!-- Modal Header -->
                            <x-slot:header>
                                <p
                                    class="text-lg text-gray-800 dark:text-white font-bold"
                                    v-if="! attributes.length"
                                >
                                    @lang('admin::app.catalog.products.index.create.title')
                                </p>

                                <p
                                    class="text-lg text-gray-800 dark:text-white font-bold"
                                    v-else
                                >
                                    @lang('admin::app.catalog.products.index.create.configurable-attributes')
                                </p>
                            </x-slot>

                            <!-- Modal Content -->
                            <x-slot:content>
                                <div v-show="! attributes.length">
                                    {!! view_render_event('unopim.admin.catalog.products.create_form.general.controls.before') !!}

                                    <!-- Product Type -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.catalog.products.index.create.type')
                                        </x-admin::form.control-group.label>

                                        @php
                                            $supportedTypes  = config('product_types');

                                            $types = [];

                                            foreach($supportedTypes as $id => $type) {
                                                $types[] = [
                                                    'id'    => $id,
                                                    'label' => trans($type['name'])
                                                ];
                                            }
                                        @endphp

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="type"
                                            rules="required"
                                            :label="trans('admin::app.catalog.products.index.create.type')"
                                            :options="json_encode($types)"
                                            track-by="id"
                                            label-by="label"
                                        >
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.error control-name="type" />
                                    </x-admin::form.control-group>

                                    <!-- Attribute Family Id -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.catalog.products.index.create.family')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="attribute_family_id"
                                            rules="required"
                                            :label="trans('admin::app.catalog.products.index.create.family')"
                                            entity-name="attribute_family"
                                            track-by="id"
                                            label-by="label"
                                            async="true"
                                        >
                                        </x-admin::form.control-group.control>

                                        <x-admin::form.control-group.error control-name="attribute_family_id" />
                                    </x-admin::form.control-group>

                                    <!-- SKU -->
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.catalog.products.index.create.sku')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="sku"
                                            ::rules="{ required: true, regex: /^[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*$/ }"
                                            :label="trans('admin::app.catalog.products.index.create.sku')"
                                        />

                                        <x-admin::form.control-group.error control-name="sku" />
                                    </x-admin::form.control-group>

                                    {!! view_render_event('unopim.admin.catalog.products.create_form.general.controls.before') !!}
                                </div>

                                <div v-show="attributes.length">
                                    {!! view_render_event('unopim.admin.catalog.products.create_form.attributes.controls.before') !!}

                                    <div
                                        class="mb-2.5"
                                    >
                                        <label
                                            class="block leading-6 text-xs text-gray-800 dark:text-white font-medium"
                                        >
                                        </label>

                                        <x-admin::form.control-group.control
                                            type="tagging"
                                            name="attributes"
                                            ::value="attributes"
                                            ::options="JSON.stringify(attributes)"
                                            track-by="code"
                                            label-by="name"
                                        />
                                    </div>

                                    {!! view_render_event('unopim.admin.catalog.products.create_form.attributes.controls.before') !!}
                                </div>
                            </x-slot>

                            <!-- Modal Footer -->
                            <x-slot:footer>
                                <!-- Modal Submission -->
                                <div class="flex gap-x-2.5 items-center">
                                    <button
                                        type="button"
                                        class="transparent-button hover:bg-violet-100 dark:hover:bg-gray-800 dark:text-white"
                                        v-if="attributes.length"
                                        @click="attributes = []"
                                    >
                                        @lang('admin::app.catalog.products.index.create.back-btn')
                                    </button>

                                    <button
                                        type="submit"
                                        class="primary-button"
                                    >
                                        @lang('admin::app.catalog.products.index.create.save-btn')
                                    </button>
                                </div>
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-create-product-form', {
                template: '#v-create-product-form-template',

                data() {
                    return {
                        attributes: [],

                        superAttributes: {}
                    };
                },

                methods: {
                    create(params, {
                        setErrors
                    }) {
                        this.superAttributes = {};

                        let formData = new FormData(this.$refs.productCreateForm);

                        formData.entries().forEach(entry => {
                            if (entry[0] === 'attributes') {
                                entry[1].split(',').forEach(code => {
                                    if (code !== '')
                                        this.superAttributes[code] = code;
                                });
                            }
                        });

                        if (Object.entries(this.superAttributes)?.length > 0) {
                            formData.append('super_attributes', JSON.stringify(this.superAttributes));
                        }

                        this.$axios.post("{{ route('admin.catalog.products.store') }}", formData)
                            .then((response) => {
                                if (response.data.data.redirect_url) {
                                    window.location.href = response.data.data.redirect_url;
                                } else {
                                    this.attributes = response.data.data.attributes;
                                }
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    }
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts>

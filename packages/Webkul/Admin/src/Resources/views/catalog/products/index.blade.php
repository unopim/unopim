<x-admin::layouts>
    <x-admin::products.bulk-edit-modal />
    <x-slot:title>
        @lang('admin::app.catalog.products.index.title')
        </x-slot>

        <x-admin::page-header :title="trans('admin::app.catalog.products.index.title')">
            <x-slot:actions>
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
            </x-slot>
        </x-admin::page-header>

        {!! view_render_event('unopim.admin.catalog.products.list.before') !!}

    <!-- Datagrid -->
    <x-admin::datagrid
        src="{{ route('admin.catalog.products.index') }}"
        filter-attributes-src="{{ route('admin.catalog.products.filterable_attributes') }}"
        :isMultiRow="true"
    >

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
                                    v-if="! variantStructures.length"
                                >
                                    @lang('admin::app.catalog.products.index.create.title')
                                </p>

                                <p
                                    class="text-lg text-gray-800 dark:text-white font-bold"
                                    v-else
                                >
                                    @lang('admin::app.catalog.products.index.create.variant-structure')
                                </p>
                            </x-slot>

                            <!-- Modal Content -->
                            <x-slot:content>
                                <div v-show="! variantStructures.length">
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
                                                if ($type['internal'] ?? false) {
                                                    continue;
                                                }

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
                                            @input="type = $event ? JSON.parse($event).id : null"
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
                                            ::rules="{ required: true, regex: /^[a-zA-Z0-9]+(?:[-_][a-zA-Z0-9]+)*$/ }"
                                            :label="trans('admin::app.catalog.products.index.create.sku')"
                                        />

                                        <x-admin::form.control-group.error control-name="sku" />
                                    </x-admin::form.control-group>

                                    {!! view_render_event('unopim.admin.catalog.products.create_form.general.controls.before') !!}
                                </div>

                                <div v-if="variantStructures.length">
                                    {!! view_render_event('unopim.admin.catalog.products.create_form.attributes.controls.before') !!}

                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            @lang('admin::app.catalog.products.index.create.variant-structure')
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="select"
                                            name="variant_structure_id"
                                            rules="required"
                                            :label="trans('admin::app.catalog.products.index.create.variant-structure')"
                                            ::options="JSON.stringify(variantStructures.map(s => ({ id: s.id, label: s.name + ' (' + (s.levels == 2 ? 2 : 1) + '-level)' })))"
                                            track-by="id"
                                            label-by="label"
                                        />

                                        <x-admin::form.control-group.error control-name="variant_structure_id" />
                                    </x-admin::form.control-group>

                                    {!! view_render_event('unopim.admin.catalog.products.create_form.attributes.controls.before') !!}
                                </div>
                            </x-slot>

                            <!-- Modal Footer -->
                            <x-slot:footer>
                                <!-- Modal Submission -->
                                <div class="flex gap-x-2.5 items-center">
                                    <button
                                        type="button"
                                        class="transparent-button hover:bg-primary-100 dark:hover:bg-gray-800 dark:text-white"
                                        v-if="variantStructures.length"
                                        @click="variantStructures = []"
                                    >
                                        @lang('admin::app.catalog.products.index.create.back-btn')
                                    </button>

                                    <button
                                        type="submit"
                                        class="primary-button"
                                    >
                                        <span v-if="type === 'configurable' && ! variantStructures.length">
                                            @lang('admin::app.catalog.products.index.create.next-btn')
                                        </span>

                                        <span v-else>
                                            @lang('admin::app.catalog.products.index.create.save-btn')
                                        </span>
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
                        type: null,

                        variantStructures: [],
                    };
                },

                methods: {
                    create(params, { setErrors }) {
                        let formData = new FormData(this.$refs.productCreateForm);

                        this.$axios.post("{{ route('admin.catalog.products.store') }}", formData)
                            .then((response) => {
                                if (response.data.data.redirect_url) {
                                    this.$navigate(response.data.data.redirect_url);
                                } else if (response.data.data.variant_structures) {
                                    this.variantStructures = response.data.data.variant_structures;
                                }
                            })
                            .catch(error => {
                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },
                }
            })
        </script>
        @endPushOnce
</x-admin::layouts>

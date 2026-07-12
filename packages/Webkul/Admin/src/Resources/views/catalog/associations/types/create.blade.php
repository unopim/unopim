{{--
    Code-only create modal for association types, rendered on the index page.

    The modal collects the `code` only; on success the store() redirect is
    converted to a JSON `redirect_url` (ajax form contract) and the browser is
    sent to the edit page to configure labels, per-link fields and status.
--}}
<v-association-type-create></v-association-type-create>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-association-type-create-template"
    >
        <div>
            {!! view_render_event('unopim.admin.catalog.association_types.create.before') !!}

            @if (bouncer()->hasPermission('catalog.association_types.create'))
                <button
                    type="button"
                    class="primary-button"
                    @click="$refs.associationTypeCreateModal.toggle()"
                >
                    @lang('admin::app.catalog.association_types.index.create-btn')
                </button>
            @endif

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form
                    @submit="handleSubmit($event, create)"
                    ref="associationTypeCreateForm"
                >
                    <x-admin::modal ref="associationTypeCreateModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('admin::app.catalog.association_types.create.title')
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            {!! view_render_event('unopim.admin.catalog.association_types.create.form_controls.before') !!}

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.category_fields.create.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="code"
                                    rules="required"
                                    :label="trans('admin::app.catalog.category_fields.create.code')"
                                    :placeholder="trans('admin::app.catalog.category_fields.create.code')"
                                    v-code
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>

                            {!! view_render_event('unopim.admin.catalog.association_types.create.form_controls.after') !!}
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('admin::app.catalog.association_types.create.save-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>

            {!! view_render_event('unopim.admin.catalog.association_types.create.after') !!}
        </div>
    </script>

    <script type="module">
        app.component('v-association-type-create', {
            template: '#v-association-type-create-template',

            methods: {
                create(params, { setErrors }) {
                    let formData = new FormData(this.$refs.associationTypeCreateForm);

                    this.$axios.post("{{ route('admin.catalog.association_types.store') }}", formData)
                        .then((response) => {
                            if (response.data.redirect_url) {
                                this.$navigate(response.data.redirect_url);
                            }
                        })
                        .catch(error => {
                            if (error.response?.status == 422) {
                                setErrors(error.response.data.errors);
                            }
                        });
                },
            },
        });
    </script>
@endPushOnce

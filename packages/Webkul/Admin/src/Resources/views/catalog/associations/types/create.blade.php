{{--
    Create modal for association types, rendered on the index page.

    The modal collects the requested-locale `name` and the `code` (auto-generated
    from the name via v-code-generator, still editable). On success the store()
    redirect is converted to a JSON `redirect_url` (ajax form contract) and the
    browser is sent to the edit page to configure per-link fields and status.
--}}
@php
    $currentLocaleCode = core()->getRequestedLocaleCode();
@endphp

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
                    method="POST"
                    action="{{ route('admin.catalog.association_types.store') }}"
                    @submit="handleSubmit($event, onAjaxSubmit)"
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
                                <x-admin::form.control-group.label
                                    class="w-full"
                                    localizable="true"
                                    :current-locale-code="$currentLocaleCode"
                                >
                                    @lang('admin::app.catalog.association_types.create.name')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocaleCode }}[name]"
                                    v-code-generator="'code'"
                                    :label="trans('admin::app.catalog.association_types.create.name')"
                                    :placeholder="trans('admin::app.catalog.association_types.create.enter-name')"
                                />

                                <x-admin::form.control-group.error control-name="{{ $currentLocaleCode }}[name]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.association_types.create.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="code"
                                    rules="required"
                                    v-code
                                    :label="trans('admin::app.catalog.association_types.create.code')"
                                    :placeholder="trans('admin::app.catalog.association_types.create.enter-code')"
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
                onAjaxSubmit(values, context) {
                    return this.$root.onAjaxSubmit(values, {
                        ...context,
                        evt: { target: this.$refs.associationTypeCreateForm },
                    });
                },
            },
        });
    </script>
@endPushOnce

@php
    $currentLocaleCode = core()->getRequestedLocaleCode();
@endphp

<v-passport-template-create></v-passport-template-create>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-passport-template-create-template"
    >
        <div>
            @if (bouncer()->hasPermission('catalog.passport.template.create'))
                <button
                    type="button"
                    class="primary-button"
                    @click="$refs.passportTemplateCreateModal.toggle()"
                >
                    @lang('passport::app.templates.index.create-btn')
                </button>
            @endif

            <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form
                    method="POST"
                    action="{{ route('admin.catalog.passports.templates.store') }}"
                    @submit="handleSubmit($event, onAjaxSubmit)"
                    ref="passportTemplateCreateForm"
                >
                    <x-admin::modal ref="passportTemplateCreateModal">
                        <x-slot:header>
                            <p class="text-lg text-gray-800 dark:text-white font-bold">
                                @lang('passport::app.templates.create.title')
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label
                                    class="w-full required"
                                    localizable="true"
                                    :current-locale-code="$currentLocaleCode"
                                >
                                    @lang('passport::app.templates.create.name')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="{{ $currentLocaleCode }}[name]"
                                    rules="required"
                                    v-code-generator="'code'"
                                    :label="trans('passport::app.templates.create.name')"
                                    :placeholder="trans('passport::app.templates.create.name-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="{{ $currentLocaleCode }}[name]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('passport::app.templates.create.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="code"
                                    rules="required"
                                    v-code
                                    :label="trans('passport::app.templates.create.code')"
                                    :placeholder="trans('passport::app.templates.create.code-placeholder')"
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>
                        </x-slot>

                        <x-slot:footer>
                            <button
                                type="submit"
                                class="primary-button"
                            >
                                @lang('passport::app.templates.create.save-btn')
                            </button>
                        </x-slot>
                    </x-admin::modal>
                </form>
            </x-admin::form>
        </div>
    </script>

    <script type="module">
        app.component('v-passport-template-create', {
            template: '#v-passport-template-create-template',

            methods: {
                onAjaxSubmit(values, context) {
                    return this.$root.onAjaxSubmit(values, {
                        ...context,
                        evt: { target: this.$refs.passportTemplateCreateForm },
                    });
                },
            },
        });
    </script>
@endPushOnce

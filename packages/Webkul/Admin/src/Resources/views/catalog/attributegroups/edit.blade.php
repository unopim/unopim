<x-admin::layouts.with-history>
    <x-slot:entityName>
        attributeGroup
    </x-slot>

    <x-slot:title>
        @lang('admin::app.catalog.attribute-groups.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.catalog.attribute-groups.edit.title')"
            :back-url="route('admin.catalog.attribute.groups.index')"
            :back-label="trans('admin::app.catalog.attribute-groups.edit.back-btn')"
            form="attribute-group-edit-form"
            :sticky="false"
        />
    </x-slot>

    <v-edit-attribute-groups :locales="{{ $locales->toJson() }}"></v-edit-attribute-groups>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-edit-attribute-groups-template-{{ $attributeGroup->id }}"
        >
            {!! view_render_event('unopim.admin.catalog.attribute.groups.edit.before') !!}

            <x-admin::form
                id="attribute-group-edit-form"
                ajax
                :action="route('admin.catalog.attribute.groups.update', $attributeGroup->id)"
                enctype="multipart/form-data"
                method="PUT"
            >
                
                {!! view_render_event('unopim.admin.catalog.attribute.groups.create._form_controls.before') !!}

                <div class="flex gap-2.5 max-xl:flex-wrap">
                    <div class="flex flex-col flex-1 gap-2 max-xl:flex-auto">

                        {!! view_render_event('unopim.admin.catalog.attributes.edit.card.label.before', ['attributeGroup' => $attributeGroup]) !!}

                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.catalog.attribute-groups.edit.general')
                            </p>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.attribute-groups.edit.code')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    class="cursor-not-allowed"
                                    name="code"
                                    rules="required"
                                    :value="$attributeGroup->code"
                                    :disabled="(boolean) $attributeGroup->code"
                                    readonly
                                    :label="trans('admin::app.catalog.attribute-groups.edit.code')"
                                    :placeholder="trans('admin::app.catalog.attribute-groups.edit.code')"
                                />

                                <x-admin::form.control-group.control
                                    type="hidden"
                                    name="code"
                                    :value="$attributeGroup->code"
                                />

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>

                            
                        </div>

                        {!! view_render_event('unopim.admin.catalog.attribute.groups.edit.card.label.after', ['attributeGroup' => $attributeGroup]) !!}

                        <div class="bg-white dark:bg-cherry-900 box-shadow rounded">
                            <div class="flex justify-between items-center p-1.5">
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.attributes.edit.label')
                                </p>
                            </div>

                            <div class="px-4 pb-4">
                                <!-- Locales Inputs -->
                                <x-admin::form.translatable-field
                                    :locales="$locales"
                                    :values="collect($locales)->mapWithKeys(fn ($locale) => [$locale->code => old($locale->code)['name'] ?? ($attributeGroup->translate($locale->code)->name ?? '')])->all()"
                                    :label="trans('admin::app.catalog.attribute-groups.edit.label')"
                                />
                            </div>
                        </div>
                    </div>

                    {!! view_render_event('unopim.admin.catalog.attribute.groups.edit.card.accordian.validations.before', ['attributeGroup' => $attributeGroup]) !!}

                    {!! view_render_event('unopim.admin.catalog.attribute.groups.edit.card.accordian.configuration.configuration.after', ['attributeGroup' => $attributeGroup]) !!}
                </div>
            </x-admin::form>

            {!! view_render_event('unopim.admin.catalog.attribute.groups.edit.after') !!}

        </script>

        <script type="module">
            app.component('v-edit-attribute-groups', {
                template: '#v-edit-attribute-groups-template-{{ $attributeGroup->id }}',

                props: ['locales'],

                methods: {
                    onAjaxSubmit(...args) {
                        return this.$root.onAjaxSubmit(...args);
                    },

                    onInvalidSubmit(...args) {
                        return this.$root.onInvalidSubmit(...args);
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts.with-history>

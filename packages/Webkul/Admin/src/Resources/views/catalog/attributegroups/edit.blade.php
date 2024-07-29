<x-admin::layouts.with-history>
    <x-slot:entityName>
        attributeGroup
    </x-slot>

    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.catalog.attribute-groups.edit.title')
    </x-slot>

    <!-- Edit Attributes Vue Components -->
    <v-edit-attribute-groups :locales="{{ $locales->toJson() }}"></v-edit-attribute-groups>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-edit-attribute-groups-template"
        >
            {!! view_render_event('unopim.admin.catalog.attribute.groups.edit.before') !!}

            <!-- Input Form -->
            <x-admin::form
                :action="route('admin.catalog.attribute.groups.update', $attributeGroup->id)"
                enctype="multipart/form-data"
                method="PUT"
            >
                
                {!! view_render_event('unopim.admin.catalog.attribute.groups.create._form_controls.before') !!}

                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.catalog.attribute-groups.edit.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <!-- Back Button -->
                        <a
                            href="{{ route('admin.catalog.attribute.groups.index') }}"
                            class="transparent-button"
                        >
                            @lang('admin::app.catalog.attribute-groups.edit.back-btn')
                        </a>

                        <!-- Save Button -->
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.catalog.attribute-groups.edit.save-btn')
                        </button>
                    </div>
                </div>

                <!-- body content -->
                <div class="flex gap-2.5 mt-3.5">
                    <!-- Left sub Component -->
                    <div class="flex flex-col flex-1 gap-2 overflow-auto">

                        {!! view_render_event('unopim.admin.catalog.attributes.edit.card.label.before', ['attributeGroup' => $attributeGroup]) !!}

                        <!-- Label -->
                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.catalog.attribute-groups.edit.general')
                            </p>
                                <!-- Attribute Group Code -->
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

                        <!-- Labels -->
                        <div class="bg-white dark:bg-cherry-900 box-shadow rounded">
                            <div class="flex justify-between items-center p-1.5">
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                                    @lang('admin::app.catalog.attributes.edit.label')
                                </p>
                            </div>

                            <div class="px-4 pb-4">
                                <!-- Locales Inputs -->
                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            {{ $locale->name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            :name="$locale->code . '[name]'"
                                            :value="old($locale->code)['name'] ?? ($attributeGroup->translate($locale->code)->name ?? '')"
                                        />

                                        <x-admin::form.control-group.error :control-name="$locale->code . '[name]'" />
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Right sub-component -->
                    <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                        {!! view_render_event('unopim.admin.catalog.attribute.groups.edit.card.accordian.validations.before', ['attributeGroup' => $attributeGroup]) !!}

                        {!! view_render_event('unopim.admin.catalog.attribute.groups.edit.card.accordian.configuration.configuration.after', ['attributeGroup' => $attributeGroup]) !!}
                    </div>
                </div>
            </x-admin::form>


            {!! view_render_event('unopim.admin.catalog.attribute.groups.edit.after') !!}

        </script>

        <script type="module">
            app.component('v-edit-attribute-groups', {
                template: '#v-edit-attribute-groups-template',

                props: ['locales'],
            });
        </script>
    @endPushOnce
</x-admin::layouts.with-history>

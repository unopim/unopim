@php
    $locales = app('Webkul\Core\Repositories\LocaleRepository')->getActiveLocales();
@endphp

<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.catalog.attribute-groups.create.title')
    </x-slot>

    <!-- Create Attributes Vue Components -->
    <v-create-attribute-groups :locales="{{ $locales->toJson() }}"></v-create-attribute-groups>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-create-attribute-groups-template"
        >

            {!! view_render_event('unopim.admin.catalog.attributes.create.before') !!}

            <!-- Input Form -->
            <x-admin::form
                :action="route('admin.catalog.attribute.groups.store')"
                enctype="multipart/form-data"
            >

                {!! view_render_event('unopim.admin.catalog.attribute.groups.create.create_form_controls.before') !!}

                <!-- actions buttons -->
                <div class="flex justify-between items-center">
                    <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                        @lang('admin::app.catalog.attribute-groups.create.title')
                    </p>

                    <div class="flex gap-x-2.5 items-center">
                        <!-- Cancel Button -->
                        <a
                            href="{{ route('admin.catalog.attribute.groups.index') }}"
                            class="transparent-button"
                        >
                            @lang('admin::app.catalog.attribute-groups.create.back-btn')
                        </a>

                        <!-- Save Button -->
                        <button
                            type="submit"
                            class="primary-button"
                        >
                            @lang('admin::app.catalog.attribute-groups.create.save-btn')
                        </button>
                    </div>
                </div>

                <!-- body content -->
                <div class="flex gap-2.5 mt-3.5">

                    {!! view_render_event('unopim.admin.catalog.attribute.groups.create.card.label.before') !!}

                    <!-- Left sub Component -->
                    <div class="flex flex-col gap-2 flex-1 overflow-auto">
                        <!-- General -->
                        <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.catalog.attribute-groups.create.general')
                            </p>

                            <!-- Attribute  Group Code -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.catalog.attribute-groups.create.code')
                                </x-admin::form.control-group.label>

                                <v-field
                                    type="text"
                                    name="code"
                                    rules="required"
                                    value="{{ old('code') }}"
                                    v-slot="{ field }"
                                    label="{{ trans('admin::app.catalog.attribute-groups.create.code') }}"
                                >
                                    <input
                                        type="text"
                                        id="code"
                                        :class="[errors['{{ 'code' }}'] ? 'border border-red-600 hover:border-red-600' : '']"
                                        class="flex w-full min-h-[39px] py-2 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 dark:focus:border-gray-400 focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-800"
                                        name="slug"
                                        v-bind="field"
                                        placeholder="{{ trans('admin::app.catalog.attribute-groups.create.code') }}"
                                        v-code
                                    >
                                </v-field>

                                <x-admin::form.control-group.error control-name="code" />
                            </x-admin::form.control-group>
                        </div>

                        <!-- Labels -->
                        <div class="bg-white dark:bg-cherry-900 box-shadow rounded">
                            <div class="flex justify-between items-center p-1.5">
                                <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">                                    
                                    @lang('admin::app.catalog.attribute-groups.create.label')
                                </p>
                            </div>

                            <div class="px-4 pb-4">
                                <!-- Locales Inputs -->
                                @foreach ($locales as $locale)
                                    <x-admin::form.control-group class="last:!mb-0">
                                        <x-admin::form.control-group.label>
                                            {{ $locale->name }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            :name="$locale->code . '[name]'"
                                            :value="old($locale->code)['name'] ?? ''"
                                        />
                                    </x-admin::form.control-group>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {!! view_render_event('unopim.admin.catalog.attribute.groups.create.card.label.after') !!}

                    {!! view_render_event('unopim.admin.catalog.attribute.groups.create.card.general.before') !!}

                    <!-- Right sub-component -->
                    <div class="flex flex-col gap-2 w-[360px] max-w-full">
                      
                    </div>

                    {!! view_render_event('unopim.admin.catalog.attribute.groups.create.card.general.after') !!}

                </div>

                {!! view_render_event('unopim.admin.catalog.attribute.groups.create_form_controls.after') !!}
            </x-admin::form>

            {!! view_render_event('unopim.admin.catalog.attribute.groups.create.after') !!}

        </script>

        <script type="module">
            app.component('v-create-attribute-groups', {
                template: '#v-create-attribute-groups-template',

                props: ['locales'],
            });
        </script>
    @endPushOnce
</x-admin::layouts>

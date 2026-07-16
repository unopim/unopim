<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.channels.create.title')
    </x-slot>

    {!! view_render_event('unopim.admin.settings.channels.create.before') !!}

    <x-admin::form  ajax action="{{ route('admin.settings.channels.store') }}">

        {!! view_render_event('admin.settings.channels.create.create_form_controls.before') !!}

        <x-admin::page-header :title="trans('admin::app.settings.channels.create.title')">
            <x-slot:actions>
                <a
                    href="{{ route('admin.settings.channels.index') }}"
                    class="transparent-button"
                >
                    @lang('admin::app.settings.channels.create.cancel')
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('admin::app.settings.channels.create.save-btn')
                </button>
            </x-slot>
        </x-admin::page-header>

        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">


                {!! view_render_event('unopim.admin.settings.channels.create.card.general.before') !!}

                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('admin::app.settings.channels.create.general')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.create.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="code"
                            name="code"
                            rules="required"
                            :value="old('code')"
                            :label="trans('admin::app.settings.channels.create.code')"
                            :placeholder="trans('admin::app.settings.channels.create.code')"
                        />

                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>
 
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.create.root-category')
                        </x-admin::form.control-group.label>

                        @php
                            $options = json_encode($rootCategories->toArray());
                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            id="root_category_id"
                            name="root_category_id"
                            rules="required"
                            :options="$options"
                            :value="old('root_category_id')"
                            :label="trans('admin::app.settings.channels.create.root-category')"
                            :placeholder="trans('admin::app.settings.channels.create.select-root-category')"
                            track-by="id"
                            label-by="name"
                        >

                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="root_category_id" />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('unopim.admin.settings.channels.create.card.general.after') !!}


                {!! view_render_event('unopim.admin.settings.channels.create.card.translations.before') !!}

                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('admin::app.settings.channels.edit.name-translations')
                    </p>

                     @foreach (core()->getAllActiveLocales() as $locale)
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                {{ $locale->name }} 
                            </x-admin::form.control-group.label>
    
                            <x-admin::form.control-group.control
                                type="text"
                                :id="$locale->code . '[name]'"
                                :name="$locale->code . '[name]'"
                                :label="trans('admin::app.settings.channels.edit.name') . ' (' . $locale->name . ')'"
                            />
                            <x-admin::form.control-group.error :control-name="'name-' .$locale->code" />
                        </x-admin::form.control-group>
                     @endforeach
                </div>

                {!! view_render_event('unopim.admin.settings.channels.create.card.translations.after') !!}               

            </div>

            <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">


                {!! view_render_event('unopim.admin.settings.channels.create.card.accordion.currencies_and_locales.before') !!}

                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
                            @lang('admin::app.settings.channels.create.currencies-and-locales')
                        </p>
                    </x-slot>
            
                    <x-slot:content>
                        <x-admin::form.control-group class="mb-4">
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.channels.edit.locales')
                            </x-admin::form.control-group.label>

                            @php
                                $options = json_encode(core()->getAllActiveLocales()->toArray());
                                $oldLocales = old('locales');

                                if (is_array($oldLocales)) {
                                    $oldLocales = json_encode($oldLocales);
                                }
                            @endphp

                            <x-admin::form.control-group.control
                                type="multiselect"
                                id="locales"
                                name="locales"
                                rules="required"
                                :options="$options"
                                :value="$oldLocales"
                                :label="trans('admin::app.settings.channels.edit.locales')"
                                :placeholder="trans('admin::app.settings.channels.edit.select-locales')"
                                track-by="id"
                                label-by="name"
                            />
                            
                            <x-admin::form.control-group.error control-name="locales" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="mb-4">
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.channels.edit.currencies')
                            </x-admin::form.control-group.label>
                        
                            @php
                                $options = json_encode(array_values(core()->getAllActiveCurrencies()->toArray()));
                            @endphp

                            <x-admin::form.control-group.control
                                type="multiselect"
                                id="currencies"
                                name="currencies"
                                rules="required"
                                :options="$options"
                                :label="trans('admin::app.settings.channels.edit.currencies')"
                                :placeholder="trans('admin::app.settings.channels.edit.select-currencies')"
                                track-by="id"
                                label-by="name"
                            >
                               
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="currencies" />
                        </x-admin::form.control-group> 
                    </x-slot>
                </x-admin::accordion>

                {!! view_render_event('unopim.admin.settings.channels.create.card.accordion.currencies_and_locales.after') !!}
            </div>
        </div>

        {!! view_render_event('admin.settings.channels.create.create_form_controls.after') !!}

    </x-admin::form> 

    {!! view_render_event('unopim.admin.settings.channels.create.after') !!}
</x-admin::layouts>

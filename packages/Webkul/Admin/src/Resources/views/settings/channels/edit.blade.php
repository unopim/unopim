<x-admin::layouts.with-history>    
    <x-slot:entityName>
        channel
    </x-slot>

    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.channels.edit.title')
    </x-slot>

    <!-- Channel Edit Form -->
    {!! view_render_event('unopim.admin.settings.channels.edit.before') !!}

    <x-admin::form  
        :action="route('admin.settings.channels.update', ['id' => $channel->id])"
    >
        @method('PUT')

        {!! view_render_event('unopim.admin.settings.channels.edit.edit_form_controls.before') !!}

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.settings.channels.edit.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <a
                    href="{{ route('admin.settings.channels.index') }}"
                    class="transparent-button"
                >
                    @lang('admin::app.settings.channels.edit.back-btn')
                </a>

                <button 
                    type="submit" 
                    class="primary-button"
                    aria-lebel="Submit"
                >
                    @lang('admin::app.settings.channels.edit.save-btn')
                </button>
            </div>
        </div>

        <!-- body content -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Section -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                <!-- General Information -->

                {!! view_render_event('unopim.admin.settings.channels.edit.card.general.before') !!}

                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('admin::app.settings.channels.edit.general')
                    </p>

                    <!-- Code -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.edit.code')
                        </x-admin::form.control-group.label>

                        @php
                            $selectedOption = old('type') ?: $channel->code;
                        @endphp

                        <x-admin::form.control-group.control
                            type="text"
                            class="cursor-not-allowed"
                            id="code"
                            name="code"
                            rules="required"
                            :value="$selectedOption"
                            :disabled="(boolean) $selectedOption"
                            readonly
                            :label="trans('admin::app.settings.channels.edit.code')"
                            :placeholder="trans('admin::app.settings.channels.edit.code')"
                        />

                        <x-admin::form.control-group.control
                            type="hidden"
                            name="code"
                            :value="$selectedOption"
                        />

                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>

                    <!-- Root Category -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.edit.root-category')
                        </x-admin::form.control-group.label>

                        @php
                        
                            $selectedOption = $channel->root_category_id;
                            
                            $options = json_encode(app('Webkul\Category\Repositories\CategoryRepository')->getRootCategories()->toArray());
                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            id="root_category_id"
                            name="root_category_id"
                            rules="required"
                            :options="$options"
                            :value="$selectedOption"
                            :label="trans('admin::app.settings.channels.create.root-category')"
                            :placeholder="trans('admin::app.settings.channels.create.select-root-category')"
                            track-by="id"
                            label-by="name"
                        >
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="root_category_id" />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('unopim.admin.settings.channels.edit.card.general.after') !!}

                <!-- Name Translations -->

                {!! view_render_event('unopim.admin.settings.channels.edit.card.translations.before') !!}

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
                                :value="old($locale->code . '.name') ?? ($channel->translate($locale->code)['name'] ?? '')"
                                :label="trans('admin::app.settings.channels.edit.name') . ' (' . $locale->name . ')'"
                            />
                            <x-admin::form.control-group.error :control-name="'name-' .$locale->code" />
                        </x-admin::form.control-group>
                    @endforeach
                </div>

                {!! view_render_event('unopim.admin.settings.channels.edit.card.translations.after') !!}
            </div>

            <!-- Right Section -->
            <div class="flex flex-col gap-2 w-[360px] max-w-full max-sm:w-full">
                <!-- Currencies and Locale -->

                {!! view_render_event('unopim.admin.settings.channels.edit.card.accordion.currencies_and_locales.before') !!}

                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-gray-800 dark:text-white text-base  font-semibold">
                                @lang('admin::app.settings.channels.edit.currencies-and-locales')
                            </p>
                        </div>
                    </x-slot>
            
                    <x-slot:content>
                        <!-- Locales Checkboxes -->
                        <x-admin::form.control-group class="mb-4">
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.channels.edit.locales')
                            </x-admin::form.control-group.label>

                            @php
                                $selectedOptions =  old('locales') ?? json_encode($channel->locales->pluck('id')->toArray());

                                $selectedOptions = is_array($selectedOptions) ? json_encode($selectedOptions, JSON_NUMERIC_CHECK) : $selectedOptions;

                                $options = json_encode(core()->getAllActiveLocales()->toArray());
                                
                            @endphp

                            <x-admin::form.control-group.control
                                type="multiselect"
                                id="locales"
                                name="locales"
                                rules="required"
                                :options="$options"
                                :value="$selectedOptions"
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
                                $selectedOptions2 =  old('currencies') ?? json_encode($channel->currencies->pluck('id')->toArray());

                                $selectedOptions2 = is_array($selectedOptions2) ? json_encode($selectedOptions2, JSON_NUMERIC_CHECK) : $selectedOptions2;

                                $options2 = json_encode(array_values(core()->getAllActiveCurrencies()->toArray()));
                            @endphp

                            <x-admin::form.control-group.control
                                type="multiselect"
                                id="currencies"
                                name="currencies"
                                rules="required"
                                :options="$options2"
                                :value="$selectedOptions2"
                                :label="trans('admin::app.settings.channels.edit.currencies')"
                                :placeholder="trans('admin::app.settings.channels.edit.select-currencies')"
                                track-by="id"
                                label-by="name"
                            />
                            
                            <x-admin::form.control-group.error control-name="currencies" />
                        </x-admin::form.control-group> 
                    </x-slot>
                </x-admin::accordion>

                {!! view_render_event('unopim.admin.settings.channels.edit.card.accordion.currencies_and_locales.after') !!}
            </div>
        </div>

        {!! view_render_event('unopim.admin.settings.channels.edit.edit_form_controls.after') !!}

    </x-admin::form> 

    {!! view_render_event('unopim.admin.settings.channels.edit.after') !!}
    
</x-admin::layouts.with-history>

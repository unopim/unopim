<x-admin::layouts.with-history>
    <x-slot:entityName>
        shopify_credentials
    </x-slot>

    <x-slot:title>
        @lang('shopify::app.shopify.credential.index.title')
    </x-slot>
    
    <x-admin::form  
        :action="route('shopify.credentials.update', ['id' => $credential->id])"
    >
        @method('PUT')

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('shopify::app.shopify.credential.edit.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <a
                    href="{{ route('shopify.credentials.index') }}"
                    class="transparent-button"
                >
                    @lang('shopify::app.shopify.credential.edit.back-btn')
                </a>

                <button 
                    type="submit" 
                    class="primary-button"
                    aria-label="Submit"
                >
                    @lang('shopify::app.shopify.credential.edit.save')
                </button>
            </div>
        </div>

        <!-- body content -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <!-- Left Section -->
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">

                <!-- General Information -->

                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('admin::app.settings.channels.edit.general')
                    </p>

                    <!-- shopUrl -->
                    <x-admin::form.control-group class="w-[525px]">
                        <x-admin::form.control-group.label class="required">
                            @lang('shopify::app.shopify.credential.index.url')
                        </x-admin::form.control-group.label >

                        <x-admin::form.control-group.control
                            type="text"
                            id="shopUrl"
                            name="shopUrl"
                            rules="required"
                            :value="old('shopUrl') ?? $credential->shopUrl"
                            :label="trans('shopify::app.shopify.credential.index.url')"
                            :placeholder="trans('shopify::app.shopify.credential.index.shopifyurlplaceholder')"
                            
                        />

                        <x-admin::form.control-group.error control-name="shopUrl" />
                    </x-admin::form.control-group>
                    
                    <x-admin::form.control-group class="w-[525px]">
                        <x-admin::form.control-group.label class="required">
                            @lang('shopify::app.shopify.credential.index.accesstoken')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            id="accessToken"
                            name="accessToken"
                            rules="required"
                            :value="old('accessToken') ?? $credential->accessToken"
                            :label="trans('shopify::app.shopify.credential.index.accesstoken')"
                            :placeholder="trans('shopify::app.shopify.credential.index.accesstoken')"
                        />

                        <x-admin::form.control-group.error control-name="accessToken" />
                    </x-admin::form.control-group>
                    <x-admin::form.control-group class="mb-4 w-[525px]">
                        <x-admin::form.control-group.label class="required">
                            @lang('shopify::app.shopify.credential.index.apiVersion')
                        </x-admin::form.control-group.label>

                        @php
                        
                            $apiVersion = json_encode($apiVersion, true);
                            $selectedOption = old('apiVersion') ?: $credential->apiVersion;
                            
                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            id="apiVersion"
                            name="apiVersion"
                            rules="required"
                            :label="trans('shopify::app.shopify.credential.index.apiVersion')"
                            :placeholder="trans('shopify::app.shopify.credential.index.apiVersion')"
                            :value="$selectedOption"
                            :options="$apiVersion"
                            track-by="id"
                            label-by="name"
                        />

                        <x-admin::form.control-group.error control-name="apiVersion" />
                    </x-admin::form.control-group>
                    <x-admin::form.control-group class="mb-4 w-[525px]">
                        <x-admin::form.control-group.label class="required">
                            @lang('shopify::app.shopify.credential.index.channel')
                        </x-admin::form.control-group.label>

                        @php
                            $salesChannel = $credential->extras;
                            $channel = array_column($publishingChannel, 'node');
                            $pubChannel = json_encode($channel, true);
                            $selectChannel = $salesChannel ? explode(',' , $salesChannel['salesChannel']) : array_column($channel,'id');
                            $selectedOption = json_encode($selectChannel);
                        @endphp

                        <x-admin::form.control-group.control
                            type="multiselect"
                            id="salesChannel"
                            name="salesChannel"
                            rules="required"
                            :label="trans('shopify::app.shopify.credential.index.channel')"
                            :placeholder="trans('shopify::app.shopify.credential.index.channel')"
                            :value="$selectedOption"
                            :options="$pubChannel"
                            track-by="id"
                            label-by="name"
                        />

                        <x-admin::form.control-group.error control-name="salesChannel" />
                    </x-admin::form.control-group>
                    <x-admin::form.control-group class="mb-4 w-[525px]">
                        <x-admin::form.control-group.label class="required">
                            @lang('shopify::app.shopify.credential.index.locations')
                        </x-admin::form.control-group.label>

                        @php
                            $selectLocation = $salesChannel ? explode(',' , $salesChannel['locations'])[0] : '' ;
                            $locationAll = array_column($locationAll, 'node');
                            $publocationAll = json_encode($locationAll, true);
                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            id="locations"
                            name="locations"
                            rules="required"
                            :label="trans('shopify::app.shopify.credential.index.locations')"
                            :placeholder="trans('shopify::app.shopify.credential.index.locations')"
                            :value="$selectLocation"
                            :options="$publocationAll"
                            track-by="id"
                            label-by="name"
                        />

                        <x-admin::form.control-group.error control-name="locations" />
                    </x-admin::form.control-group>
                        <!-- Enable/Disable -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.catalog.category_fields.edit.status')
                            </x-admin::form.control-group.label>
                            <input 
                                type="hidden"
                                name="active"
                                value="0"
                            />

                            <x-admin::form.control-group.control
                                type="switch"
                                name="active"
                                value="1"
                                :checked="(boolean) $credential->active"
                            />
                    </x-admin::form.control-group>
                </div>
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('shopify::app.shopify.credential.export.locales')
                    </p>
                    <div class="grid grid-cols-2 gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800">
                        <p class="break-words font-bold"> @lang('shopify::app.shopify.credential.shopify.locale')</p>
                        
                        <p class="break-words font-bold">@lang('shopify::app.shopify.credential.unopim.locale')</p>
                    </div>
                    
                        @php
                             
                            $options = core()->getAllActiveLocales();
                            $storelocaleMapping = $credential->storelocaleMapping;
                            
                            $JsonShopLocales = json_encode($shopLocales, true);
                            $allLocale = $credential->storeLocales ?? [];
                            
                            $defaultLocale = array_values(array_filter($allLocale, function($locale) {
                                return isset($locale['defaultlocale']) && $locale['defaultlocale'] === true;
                            }));
                            $defaultLocale = !empty($defaultLocale) ? $defaultLocale[0] : null;
                            
                            $defaultLocaleCode = $defaultLocale ?  $defaultLocale['locale'] : null;
                            
                        @endphp

                        <input type="hidden" name="storeLocales" class="default" value="{{ $JsonShopLocales }}">

                        @foreach ($shopLocales as $locale)
                                @php 
                                    $localeCode = $locale['locale'];
                                    $primary = $locale['primary'] ? '(Default)' : '';
                                    $selectedLocale = $storelocaleMapping[$localeCode] ?? null;
                                @endphp
                           <div class="grid grid-cols-2 gap-2.5 items-center px-4 py-4 border-b dark:border-cherry-800 text-gray-600 dark:text-gray-300 transition-all hover:bg-violet-50 hover:bg-opacity-30 dark:hover:bg-cherry-800">
                                <p class="break-words">{{ $locale['name'].' '.$locale['locale'].' '.$primary }}</p>
                                
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.control
                                        type="select"
                                        id="locales"
                                        name="storelocaleMapping[{{ $localeCode }}]"
                                        rules="required"
                                        :options="$options"
                                        :value="$selectedLocale"
                                        :label="trans('admin::app.settings.channels.edit.locales')"
                                        :placeholder="trans('admin::app.settings.channels.edit.select-locales')"
                                        track-by="code"
                                        label-by="name"
                                    />
                                    <x-admin::form.control-group.error control-name="storelocaleMapping[{{ $localeCode }}]" />
                                </x-admin::form.control-group>
                           </div>
                        @endforeach
                </div>
            </div>
        </div>
    </x-admin::form> 
    
</x-admin::layouts.with-history>

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.account.edit.title')
    </x-slot>

    <x-admin::layouts.edit-page-header
        :title="trans('admin::app.account.edit.title')"
        :back-url="route('admin.dashboard.index')"
        :back-label="trans('admin::app.account.edit.back-btn')"
        form="account-edit-form"
    />

    <!-- Input Form -->
    <x-admin::form
        id="account-edit-form"
        ajax
        :action="route('admin.account.update')"
        enctype="multipart/form-data"
        method="PUT">
        <!-- Full Panel -->
        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1">
                <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('admin::app.account.edit.general')
                    </p>
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.account.edit.profile-image')
                        </x-admin::form.control-group.label>

                        <x-admin::media.images
                            name="image"
                            :show-suggestions="false"
                            :uploaded-images="$user->image ? [['id' => 'image', 'url' => $user->image_url, 'value' => $user->image]] : []"
                        />

                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            @lang('admin::app.account.edit.upload-image-info')
                        </p>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.account.edit.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="name"
                            rules="required"
                            :value="old('name') ?: $user->name"
                            :label="trans('admin::app.account.edit.name')"
                            :placeholder="trans('admin::app.account.edit.name')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="mb-4">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.account.edit.email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="email"
                            name="email"
                            id="email"
                            rules="required"
                            :value="old('email') ?: $user->email"
                            :label="trans('admin::app.account.edit.email')"
                        />

                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="mb-4">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.account.edit.ui-locale')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="ui_locale_id"
                            name="ui_locale_id"
                            rules="required"
                            :value="old('ui_locale_id') ?: $user->ui_locale_id"
                            :label="trans('admin::app.account.edit.ui-locale')"
                            :placeholder="trans('admin::app.account.edit.ui-locale')"
                            :options="core()->getTranslatableLocales()"
                            track-by="id"
                            label-by="name"
                        >
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="ui_locale_id" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="mb-4">
                        <x-admin::form.control-group.label
                            :title="trans('admin::app.account.edit.catalog-locale-info')"
                        >
                            @lang('admin::app.account.edit.catalog-locale')

                            <span class="icon-information text-base align-middle cursor-help"></span>
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="catalog_locale_id"
                            name="catalog_locale_id"
                            :value="old('catalog_locale_id') ?: $user->catalog_locale_id"
                            :label="trans('admin::app.account.edit.catalog-locale')"
                            :placeholder="trans('admin::app.account.edit.catalog-locale')"
                            :options="core()->getAllActiveLocales()"
                            track-by="id"
                            label-by="name"
                        >
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="catalog_locale_id" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="mb-4">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.account.edit.default-channel')
                        </x-admin::form.control-group.label>

                        @php
                            $channels = core()->getAllChannels()->map(fn ($channel) => [
                                'id'   => $channel->id,
                                'name' => $channel->name ?: '['.$channel->code.']',
                            ])->values();
                        @endphp

                        <x-admin::form.control-group.control
                            type="select"
                            id="default_channel_id"
                            name="default_channel_id"
                            :value="old('default_channel_id') ?: $user->default_channel_id"
                            :label="trans('admin::app.account.edit.default-channel')"
                            :placeholder="trans('admin::app.account.edit.default-channel')"
                            :options="json_encode($channels)"
                            track-by="id"
                            label-by="name"
                        >
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="default_channel_id" />
                    </x-admin::form.control-group>

                    <!-- TImezone -->
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.account.edit.user-timezone')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="timezone"
                            rules="required"
                            :value="old('timezone') ?: $user->timezone"
                            :label="trans('admin::app.account.edit.user-timezone')"
                            :placeholder="trans('admin::app.account.edit.user-timezone')"
                            :options="json_encode(core()->getTimeZones())"
                            track-by="id"
                            label-by="label"
                        >
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="timezone" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <div class="flex flex-col gap-2 w-[360px] max-w-full max-md:w-full">
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                            @lang('admin::app.account.edit.change-password')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.account.edit.current-password')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="current_password"
                                rules="required"
                                :label="trans('admin::app.account.edit.current-password')"
                                :placeholder="trans('admin::app.account.edit.current-password')"
                                autocomplete="current-password"
                            />

                            <x-admin::form.control-group.error control-name="current_password" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.account.edit.password')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="password"
                                rules="min:{{ config('admin.auth.password_min') }}"
                                :placeholder="trans('admin::app.account.edit.password')"
                                ref="password"
                                autocomplete="new-password"
                            />

                            <x-admin::form.control-group.error control-name="password" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.account.edit.confirm-password')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="password_confirmation"
                                rules="confirmed:@password"
                                :label="trans('admin::app.account.edit.confirm-password')"
                                :placeholder="trans('admin::app.account.edit.confirm-password')"
                                autocomplete="new-password"
                            />

                            <x-admin::form.control-group.error control-name="password_confirmation" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>

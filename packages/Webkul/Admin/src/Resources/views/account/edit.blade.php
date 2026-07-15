<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.account.edit.title')
    </x-slot>

    <x-admin::form
        ajax
        :action="route('admin.account.update')"
        enctype="multipart/form-data"
        method="PUT">
        <x-admin::page-header :title="trans('admin::app.account.edit.title')">
            <x-slot:actions>
                <a
                    href="{{ route('admin.dashboard.index') }}"
                    class="transparent-button">
                    @lang('admin::app.account.edit.back-btn')
                </a>

                <button
                    type="submit"
                    class="primary-button">
                    @lang('admin::app.account.edit.save-btn')
                </button>
            </x-slot>
        </x-admin::page-header>

        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1">
                <div class="p-4 bg-white dark:bg-cherry-900 box-shadow rounded">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('admin::app.account.edit.general')
                    </p>
                    <x-admin::form.control-group>
                        <x-admin::media.images
                            name="image"
                            :show-suggestions="false"
                            :uploaded-images="$user->image ? [['id' => 'image', 'url' => $user->image_url, 'value' => $user->image]] : []"
                        />
                    </x-admin::form.control-group>

                    <p class="mb-4 text-xs text-gray-600 dark:text-gray-300">
                        @lang('admin::app.account.edit.upload-image-info')
                    </p>

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
                            />

                            <x-admin::form.control-group.error control-name="password_confirmation" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
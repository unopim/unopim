<x-admin::layouts.with-history :history-id="$user->id">
    <x-slot:entityName>
        admin
    </x-slot>

    <x-slot:title>
        {{ $pageTitle }}
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="$pageTitle"
            :back-url="$backUrl"
            :back-label="$backLabel"
            :save-label="$saveLabel"
            :form="$formId"
            :sticky="false"
        />
    </x-slot>

    @php
        $profileImages = $user->image
            ? [['id' => 'image', 'url' => $user->image_url, 'value' => $user->image]]
            : ($user->hasGravatar() ? [['id' => 'gravatar', 'url' => $user->gravatar_url, 'value' => null]] : []);

        $userChannels = core()->getAllChannels()->map(fn ($channel) => [
            'id'   => $channel->id,
            'name' => $channel->name ?: '['.$channel->code.']',
        ])->values();
    @endphp

    <x-admin::form
        :id="$formId"
        ajax
        :action="$formAction"
        method="PUT"
        enctype="multipart/form-data"
    >
        @if ($canManage)
            <x-admin::form.control-group.control
                type="hidden"
                name="id"
                :value="$user->id"
            />
        @endif

        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('admin::app.account.edit.general')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.account.edit.profile-image')
                        </x-admin::form.control-group.label>

                        <x-admin::media.image
                            name="image"
                            :show-suggestions="false"
                            :accepted-extensions="\Webkul\Core\Rules\FileOrImageValidValue::IMAGE_ALLOWED_EXTENSIONS"
                            :uploaded-images="$profileImages"
                            :instructions="trans('admin::app.settings.users.index.create.upload-image-info')"
                        />

                        <div class="flex gap-2 items-center mt-3">
                            <input type="hidden" name="use_gravatar" value="0" />

                            <x-admin::form.control-group.control
                                type="switch"
                                name="use_gravatar"
                                value="1"
                                :checked="(bool) old('use_gravatar', $user->use_gravatar)"
                            />

                            <label class="text-sm text-gray-600 dark:text-gray-300">
                                @lang('admin::app.account.edit.use-gravatar')
                            </label>
                        </div>

                        <x-admin::form.control-group.error control-name="image" />
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

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.account.edit.email')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="email"
                            id="email"
                            name="email"
                            rules="required|email"
                            :value="old('email') ?: $user->email"
                            :label="trans('admin::app.account.edit.email')"
                            placeholder="email@example.com"
                        />

                        <x-admin::form.control-group.error control-name="email" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <div class="flex flex-col gap-2 w-[360px] max-w-full max-xl:w-full">
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                            @lang('admin::app.account.edit.change-password')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        @if ($requiresCurrentPassword)
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
                        @endif

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.account.edit.password')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="password"
                                name="password"
                                rules="min:{{ config('admin.auth.password_min') }}"
                                :label="trans('admin::app.account.edit.password')"
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

                @if ($canManage)
                    <x-admin::accordion>
                        <x-slot:header>
                            <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                                @lang('admin::app.settings.users.edit.role')
                            </p>
                        </x-slot>

                        <x-slot:content>
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.users.edit.role')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="role_id"
                                    rules="required"
                                    :value="old('role_id') ?: $user->role_id"
                                    :label="trans('admin::app.settings.users.edit.role')"
                                    :placeholder="trans('admin::app.settings.users.index.create.select')"
                                    :options="$roles"
                                    track-by="id"
                                    label-by="name"
                                />

                                <x-admin::form.control-group.error control-name="role_id" />
                            </x-admin::form.control-group>

                            @if (! $isSelf)
                                <x-admin::form.control-group class="!mb-0">
                                    <x-admin::form.control-group.label>
                                        @lang('admin::app.settings.users.edit.status')
                                    </x-admin::form.control-group.label>

                                    <input type="hidden" name="status" value="0" />

                                    <x-admin::form.control-group.control
                                        type="switch"
                                        name="status"
                                        value="1"
                                        :label="trans('admin::app.settings.users.edit.status')"
                                        :checked="(bool) old('status', $user->status)"
                                    />

                                    <x-admin::form.control-group.error control-name="status" />
                                </x-admin::form.control-group>
                            @else
                                <input type="hidden" name="status" value="{{ (int) $user->status }}" />
                            @endif
                        </x-slot>
                    </x-admin::accordion>
                @endif

                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                            @lang('admin::app.account.edit.catalog-locale')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <x-admin::form.control-group>
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
                            />

                            <x-admin::form.control-group.error control-name="ui_locale_id" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label :title="trans('admin::app.account.edit.catalog-locale-info')">
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
                            />

                            <x-admin::form.control-group.error control-name="catalog_locale_id" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.account.edit.default-channel')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                id="default_channel_id"
                                name="default_channel_id"
                                :value="old('default_channel_id') ?: $user->default_channel_id"
                                :label="trans('admin::app.account.edit.default-channel')"
                                :placeholder="trans('admin::app.account.edit.default-channel')"
                                :options="json_encode($userChannels)"
                                track-by="id"
                                label-by="name"
                            />

                            <x-admin::form.control-group.error control-name="default_channel_id" />
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
                            />

                            <x-admin::form.control-group.error control-name="timezone" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts.with-history>

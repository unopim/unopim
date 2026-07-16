<x-admin::layouts.with-history>
    <x-slot:entityName>
        locale
    </x-slot>

    <x-slot:title>
        @lang('admin::app.settings.locales.index.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.settings.locales.index.edit.title')"
            :back-url="route('admin.settings.locales.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :sticky="false"
        />
    </x-slot>

    <x-admin::form
        ajax
        :action="route('admin.settings.locales.update')"
        method="PUT"
    >
        <x-admin::form.control-group.control
            type="hidden"
            name="id"
            :value="$locale->id"
        />

        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('admin::app.settings.locales.index.locale')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.locales.index.create.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="code"
                            class="cursor-not-allowed"
                            :value="$locale->code"
                            :label="trans('admin::app.settings.locales.index.create.code')"
                            readonly
                            disabled
                        />

                        <x-admin::form.control-group.control
                            type="hidden"
                            name="code"
                            :value="$locale->code"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.locales.index.create.status')
                        </x-admin::form.control-group.label>

                        <input type="hidden" name="status" value="0" />

                        <x-admin::form.control-group.control
                            type="switch"
                            name="status"
                            value="1"
                            :label="trans('admin::app.settings.locales.index.create.status')"
                            :checked="(bool) old('status', $locale->status)"
                        />

                        <x-admin::form.control-group.error control-name="status" />
                    </x-admin::form.control-group>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('admin::app.settings.locales.index.create.save-btn')
                    </button>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts.with-history>

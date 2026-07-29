<x-admin::layouts>
    <x-slot:title>
        @lang('passport::app.templates.create.title')
    </x-slot>

    {{-- Dirty tracking is off: on a create screen every field is "unsaved", so the
         tracker would replace this form's own save button with its warning bar. --}}
    <x-admin::form
        :action="route('admin.catalog.passports.templates.store')"
        method="POST"
        :track-dirty="false"
    >
        <x-admin::page-header
            :title="trans('passport::app.templates.create.title')"
            :subtitle="trans('passport::app.templates.create.info')"
            :back="route('admin.catalog.passports.templates.index')"
        >
            <x-slot:actions>
                <button type="submit" class="primary-button">
                    @lang('passport::app.templates.create.save-btn')
                </button>
            </x-slot>
        </x-admin::page-header>

        <div class="mt-4 max-w-[720px] p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('passport::app.templates.create.name')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="{{ core()->getRequestedLocaleCode() }}[name]"
                    rules="required"
                    v-code-generator="'code'"
                    :value="old(core()->getRequestedLocaleCode())['name'] ?? ''"
                    :label="trans('passport::app.templates.create.name')"
                    :placeholder="trans('passport::app.templates.create.name-placeholder')"
                />

                <x-admin::form.control-group.error :control-name="core()->getRequestedLocaleCode() . '[name]'" />
            </x-admin::form.control-group>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('passport::app.templates.create.code')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="code"
                    rules="required"
                    :value="old('code')"
                    :label="trans('passport::app.templates.create.code')"
                    :placeholder="trans('passport::app.templates.create.code-placeholder')"
                />

                <x-admin::form.control-group.error control-name="code" />
            </x-admin::form.control-group>
        </div>
    </x-admin::form>
</x-admin::layouts>

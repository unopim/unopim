<x-admin::layouts>
    <x-slot:title>
        @lang('tenant::app.tenants.create.title')
    </x-slot>

    <x-admin::form
        :action="route('admin.settings.tenants.store')"
        method="POST"
    >
        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('tenant::app.tenants.create.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <a
                    href="{{ route('admin.settings.tenants.index') }}"
                    class="transparent-button"
                >
                    @lang('tenant::app.tenants.create.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('tenant::app.tenants.create.save-btn')
                </button>
            </div>
        </div>

        <div class="flex gap-2.5 mt-3.5">
            <div class="flex flex-col gap-2 flex-1 overflow-auto">
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('tenant::app.tenants.create.name')
                        </x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="text"
                            name="name"
                            :value="old('name')"
                            rules="required"
                            :label="trans('tenant::app.tenants.create.name')"
                        />
                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('tenant::app.tenants.create.domain')
                        </x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="text"
                            name="domain"
                            :value="old('domain')"
                            rules="required"
                            :label="trans('tenant::app.tenants.create.domain')"
                        />
                        <x-admin::form.control-group.error control-name="domain" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('tenant::app.tenants.create.admin-email')
                        </x-admin::form.control-group.label>
                        <x-admin::form.control-group.control
                            type="email"
                            name="admin_email"
                            :value="old('admin_email')"
                            rules="required|email"
                            :label="trans('tenant::app.tenants.create.admin-email')"
                        />
                        <x-admin::form.control-group.error control-name="admin_email" />
                    </x-admin::form.control-group>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>

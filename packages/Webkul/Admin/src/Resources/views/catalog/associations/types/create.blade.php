<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.association_types.create.title')
    </x-slot>

    <x-admin::form
        ajax
        :action="route('admin.catalog.association_types.store')"
    >
        {!! view_render_event('unopim.admin.catalog.association_types.create.form_controls.before') !!}

        <div class="flex justify-between items-center">
            <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
                @lang('admin::app.catalog.association_types.create.title')
            </p>

            <div class="flex gap-x-2.5 items-center">
                <a
                    href="{{ route('admin.catalog.association_types.index') }}"
                    class="transparent-button"
                >
                    @lang('admin::app.catalog.category_fields.create.back-btn')
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('admin::app.catalog.association_types.create.save-btn')
                </button>
            </div>
        </div>

        <div class="flex gap-2.5 mt-3.5">
            <!-- Left Container -->
            <div class="flex flex-col gap-2 flex-1 overflow-auto">
                {!! view_render_event('unopim.admin.catalog.association_types.create.fields.before') !!}

                <x-admin::associations.field-builder :fields="old('fields') ?? []" />

                {!! view_render_event('unopim.admin.catalog.association_types.create.fields.after') !!}
            </div>

            <!-- Right Container -->
            <div class="flex flex-col gap-2 w-[360px] max-w-full select-none">
                <!-- General -->
                <div class="relative p-[16px] bg-white dark:bg-cherry-800 rounded-[4px] box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('admin::app.catalog.category_fields.create.general')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.catalog.category_fields.create.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="code"
                            rules="required"
                            :value="old('code')"
                            :label="trans('admin::app.catalog.category_fields.create.code')"
                            :placeholder="trans('admin::app.catalog.category_fields.create.code')"
                            v-code
                        />

                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.catalog.category_fields.create.status')
                        </x-admin::form.control-group.label>

                        <input
                            type="hidden"
                            name="status"
                            value="0"
                        />

                        <x-admin::form.control-group.control
                            type="switch"
                            name="status"
                            value="1"
                            :checked="1 == (old('status') ?? 1)"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.catalog.category_fields.create.position')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="number"
                            name="position"
                            rules="required|numeric|min_value:0"
                            :value="old('position') ?? 0"
                        />

                        <x-admin::form.control-group.error control-name="position" />
                    </x-admin::form.control-group>
                </div>

                <!-- Label -->
                <div class="relative p-[16px] bg-white dark:bg-cherry-800 rounded-[4px] box-shadow">
                    <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                        @lang('admin::app.catalog.category_fields.create.label')
                    </p>

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

                            <x-admin::form.control-group.error :control-name="$locale->code . '[name]'" />
                        </x-admin::form.control-group>
                    @endforeach
                </div>
            </div>
        </div>

        {!! view_render_event('unopim.admin.catalog.association_types.create.form_controls.after') !!}
    </x-admin::form>
</x-admin::layouts>

<x-admin::layouts.with-history>
    <x-slot:entityName>
        magicSystemPrompt
    </x-slot>

    <x-slot:title>
        @lang('admin::app.configuration.system-prompt.create.edit-title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.configuration.system-prompt.create.edit-title')"
            :back-url="route('admin.magic_ai.system_prompt.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :sticky="false"
        />
    </x-slot>

    <x-admin::form
        ajax
        :action="route('admin.magic_ai.system_prompt.update')"
        method="PUT"
    >
        <x-admin::form.control-group.control
            type="hidden"
            name="id"
            :value="$prompt->id"
        />

        <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
            <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <p class="text-base text-gray-800 dark:text-white font-semibold mb-4">
                        @lang('admin::app.configuration.system-prompt.create.title')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.system-prompt.create.label-title')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="title"
                            rules="required"
                            :value="old('title') ?: $prompt->title"
                            :label="trans('admin::app.configuration.system-prompt.create.label-title')"
                            :placeholder="trans('admin::app.configuration.system-prompt.create.label-title')"
                        />

                        <x-admin::form.control-group.error control-name="title" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.configuration.system-prompt.datagrid.status')
                        </x-admin::form.control-group.label>

                        <input type="hidden" name="is_enabled" value="0" />

                        <x-admin::form.control-group.control
                            type="switch"
                            name="is_enabled"
                            value="1"
                            :label="trans('admin::app.configuration.system-prompt.datagrid.status')"
                            :checked="(bool) old('is_enabled', $prompt->is_enabled)"
                        />

                        <x-admin::form.control-group.error control-name="is_enabled" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.system-prompt.create.max-tokens')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="number"
                            name="max_tokens"
                            rules="required"
                            min="1"
                            max="32768"
                            step="1"
                            :value="old('max_tokens') ?: $prompt->max_tokens"
                            :label="trans('admin::app.configuration.system-prompt.create.max-tokens')"
                            :placeholder="trans('admin::app.configuration.system-prompt.create.max-tokens')"
                        />

                        <x-admin::form.control-group.error control-name="max_tokens" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.system-prompt.create.temperature')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="number"
                            name="temperature"
                            rules="required"
                            min="0"
                            max="2"
                            step="0.01"
                            :value="old('temperature') ?: $prompt->temperature"
                            :label="trans('admin::app.configuration.system-prompt.create.temperature')"
                            :placeholder="trans('admin::app.configuration.system-prompt.create.temperature')"
                        />

                        <x-admin::form.control-group.error control-name="temperature" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.system-prompt.datagrid.tone')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            class="h-[220px]"
                            name="tone"
                            rules="required"
                            :value="old('tone') ?: $prompt->tone"
                            :label="trans('admin::app.configuration.system-prompt.datagrid.tone')"
                            :placeholder="trans('admin::app.configuration.system-prompt.datagrid.tone')"
                        />

                        <x-admin::form.control-group.error control-name="tone" />
                    </x-admin::form.control-group>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('admin::app.configuration.system-prompt.create.save-btn')
                    </button>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts.with-history>

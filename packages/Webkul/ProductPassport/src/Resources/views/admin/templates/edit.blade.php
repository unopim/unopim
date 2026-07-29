<x-admin::layouts.with-history>
    <x-slot:entityName>
        passport_template
    </x-slot>

    <x-slot:historyId>
        {{ $template->id }}
    </x-slot>

    <x-slot:title>
        @lang('passport::app.templates.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('passport::app.templates.edit.title')"
            :back-url="route('admin.catalog.passports.templates.index')"
            :back-label="trans('passport::app.templates.edit.back-btn')"
            :save-label="trans('passport::app.templates.edit.save-btn')"
            form="passport-template-edit-form"
            :sticky="false"
        />
    </x-slot>

    <x-admin::form
        id="passport-template-edit-form"
        ajax
        :action="route('admin.catalog.passports.templates.update', $template->id)"
        method="PUT"
    >
        <div class="flex gap-2.5 max-lg:flex-wrap">
            <div class="flex flex-col gap-2 flex-1 min-w-0">
                @include('passport::admin.templates.partials.builder', [
                    'template' => $template,
                    'locales'  => $locales,
                ])
            </div>

            <div class="flex flex-col gap-2 w-[360px] max-w-full select-none">
                <x-admin::accordion :title="trans('passport::app.templates.edit.general')">
                    <x-slot:content>
                        <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('passport::app.templates.edit.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            class="cursor-not-allowed"
                            name="code"
                            readonly
                            disabled
                            :value="$template->code"
                            :label="trans('passport::app.templates.edit.code')"
                        />

                        <x-admin::form.control-group.control
                            type="hidden"
                            name="code"
                            :value="$template->code"
                        />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('passport::app.templates.edit.enabled')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="hidden"
                            name="is_enabled"
                            value="0"
                        />

                        <x-admin::form.control-group.control
                            type="switch"
                            name="is_enabled"
                            value="1"
                            :checked="(bool) (old('is_enabled') ?? $template->is_enabled)"
                        />
                    </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>

                <x-admin::accordion :title="trans('passport::app.templates.edit.label')">
                    <x-slot:content>
                        <x-admin::form.translatable-field
                            :locales="$locales"
                            :values="collect($locales)->mapWithKeys(fn ($locale) => [$locale->code => old($locale->code)['name'] ?? ($template->translate($locale->code)->name ?? '')])->all()"
                            :label="trans('passport::app.templates.edit.label')"
                        />
                    </x-slot>
                </x-admin::accordion>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts.with-history>

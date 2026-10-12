<x-admin::layouts.with-history>
    <x-slot:entityName>
        magicPrompt
    </x-slot>

    <x-slot:title>
        @lang('admin::app.configuration.prompt.create.edit-title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.configuration.prompt.create.edit-title')"
            :back-url="route('admin.magic_ai.prompt.index')"
            :back-label="trans('admin::app.account.edit.back-btn')"
            :sticky="false"
        />
    </x-slot>

    @php
        $purposeOptions = json_encode([
            ['id' => 'text_generation', 'label' => trans('admin::app.configuration.prompt.create.text-generation')],
            ['id' => 'image_generation', 'label' => trans('admin::app.configuration.prompt.create.image-generation')],
        ]);

        $typeOptions = json_encode([
            ['id' => 'product', 'label' => trans('admin::app.configuration.prompt.datagrid.product')],
            ['id' => 'category', 'label' => trans('admin::app.configuration.prompt.datagrid.category')],
        ]);

        $toneOptions = collect(app(\Webkul\MagicAI\Repository\MagicAISystemPromptRepository::class)
            ->getAllPromptOptions())
            ->map(fn ($prompt) => [
                'id'    => $prompt['id'],
                'label' => $prompt['label'],
            ])
            ->values()
            ->toJson();
    @endphp

    <x-admin::form
        ajax
        :action="route('admin.magic_ai.prompt.update')"
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
                        @lang('admin::app.configuration.prompt.create.title')
                    </p>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.prompt.create.label-title')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="title"
                            rules="required"
                            :value="old('title') ?: $prompt->title"
                            :label="trans('admin::app.configuration.prompt.create.label-title')"
                            :placeholder="trans('admin::app.configuration.prompt.create.label-title')"
                        />

                        <x-admin::form.control-group.error control-name="title" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.prompt.create.purpose')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="purpose"
                            rules="required"
                            :value="old('purpose') ?: $prompt->purpose"
                            :options="$purposeOptions"
                            :label="trans('admin::app.configuration.prompt.create.purpose')"
                            :placeholder="trans('admin::app.configuration.prompt.create.select-purpose')"
                            track-by="id"
                            label-by="label"
                        />

                        <x-admin::form.control-group.error control-name="purpose" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.prompt.create.type')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="type"
                            rules="required"
                            :value="old('type') ?: $prompt->type"
                            :options="$typeOptions"
                            :label="trans('admin::app.configuration.prompt.create.type')"
                            :placeholder="trans('admin::app.configuration.prompt.create.type')"
                            track-by="id"
                            label-by="label"
                        />

                        <x-admin::form.control-group.error control-name="type" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.configuration.system-prompt.datagrid.tone')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="tone"
                            :value="old('tone') ?: $prompt->tone"
                            :options="$toneOptions"
                            :label="trans('admin::app.configuration.system-prompt.datagrid.tone')"
                            :placeholder="trans('admin::app.configuration.system-prompt.datagrid.tone')"
                            track-by="id"
                            label-by="label"
                        />

                        <x-admin::form.control-group.error control-name="tone" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.configuration.prompt.create.prompt')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            class="h-[220px]"
                            name="prompt"
                            rules="required"
                            :value="old('prompt') ?: $prompt->prompt"
                            :label="trans('admin::app.configuration.prompt.create.prompt')"
                            :placeholder="trans('admin::app.configuration.prompt.create.prompt')"
                        />

                        <x-admin::form.control-group.error control-name="prompt" />
                    </x-admin::form.control-group>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('admin::app.configuration.prompt.create.save-btn')
                    </button>
                </div>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts.with-history>

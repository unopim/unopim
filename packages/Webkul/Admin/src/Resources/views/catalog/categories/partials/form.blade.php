@php
    $isEdit = (bool) $category;

    $fieldValues = $category?->additional_data ?? [];

    $showParent = ($showParent ?? false) && count($treeItems ?? []);

    $isEmptyRightSection = $rightCategoryFields?->isEmpty();
@endphp

<div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
    <div class="flex flex-col gap-2 flex-1 max-xl:flex-auto">
        {!! view_render_event('unopim.admin.catalog.categories.edit.card.general.before', ['category' => $category]) !!}

        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="mb-4 text-base text-gray-800 dark:text-white font-semibold">
                @lang('admin::app.catalog.categories.edit.general')
            </p>

            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('admin::app.catalog.categories.edit.code')
                </x-admin::form.control-group.label>

                <x-admin::form.control-group.control
                    type="text"
                    name="code"
                    rules="required"
                    :class="$isEdit ? 'cursor-not-allowed' : ''"
                    :disabled="$isEdit && (bool) $category->code"
                    :value="$isEdit ? $category->code : old('code')"
                    v-code
                />

                <x-admin::form.control-group.error control-name="code" />
            </x-admin::form.control-group>

            @if ($parentLabel ?? null)
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('admin::app.catalog.categories.edit.select-parent-category')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        class="cursor-not-allowed"
                        name="parent_label"
                        disabled
                        :value="$parentLabel"
                    />
                </x-admin::form.control-group>
            @endif
        </div>

        {!! view_render_event('unopim.admin.catalog.categories.edit.card.general.after', ['category' => $category]) !!}

        @if (! $leftCategoryFields->isEmpty())
            <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                <x-admin::categories.dynamic-fields
                    :fields="$leftCategoryFields"
                    :fieldValues="$fieldValues"
                />
            </div>
        @endif
    </div>

    @if (! $isEmptyRightSection || $showParent)
        <div class="flex flex-col gap-2 w-[360px] max-w-full">
            @if ($showParent)
                <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
                    <h2 class="block mb-2.5 text-base text-gray-800 dark:text-white font-medium leading-6">
                        @lang('admin::app.catalog.categories.edit.select-parent-category')
                    </h2>

                    <div class="flex flex-col gap-3 h-[calc(100vh-100px)] overflow-y-auto">
                        <x-admin::tree.category.view
                            input-type="radio"
                            name-field="parent_id"
                            label-field="name"
                            value-field="id"
                            id-field="id"
                            children-page-size="100"
                            ::show-search="true"
                            ::show-toolbar="true"
                            ::allow-create="{{ bouncer()->hasPermission('catalog.categories.create') ? 'true' : 'false' }}"
                            ::allow-root-create="{{ bouncer()->hasPermission('catalog.categories.create') ? 'true' : 'false' }}"
                            ::allow-delete="{{ bouncer()->hasPermission('catalog.categories.delete') ? 'true' : 'false' }}"
                            :current-category="$category?->id"
                            :expanded-branch="json_encode($branchToParent)"
                            :items="json_encode($treeItems)"
                            :value="old('parent_id') ?? json_encode($category?->parent_id)"
                            :fallback-locale="config('app.fallback_locale')"
                        />
                    </div>
                </div>
            @endif

            @if (! $isEmptyRightSection)
                {!! view_render_event('unopim.admin.catalog.categories.edit.card.accordion.settings.before', ['category' => $category]) !!}

                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                            @lang('admin::app.catalog.categories.edit.right-section')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <x-admin::categories.dynamic-fields
                            :fields="$rightCategoryFields"
                            :fieldValues="$fieldValues"
                        />
                    </x-slot>
                </x-admin::accordion>

                {!! view_render_event('unopim.admin.catalog.categories.edit.card.accordion.settings.after', ['category' => $category]) !!}
            @endif
        </div>
    @endif
</div>

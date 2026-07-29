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

            @if (($parentPicker ?? false) || ($parentLabel ?? null))
                @php $parentText = $parentLabel ?: trans('admin::app.catalog.categories.browse.root-level'); @endphp

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('admin::app.catalog.categories.edit.select-parent-category')
                    </x-admin::form.control-group.label>

                    @if ($parentPicker ?? false)
                        <x-admin::drawer width="480px" ref="parentDrawer">
                            <x-slot:toggle>
                                <div class="flex gap-2.5 items-center justify-between w-full px-3 py-2 border dark:border-cherry-800 rounded-md cursor-pointer hover:border-gray-400">
                                    <span
                                        class="text-sm text-gray-600 dark:text-gray-300 truncate"
                                        ref="parentPathLabel"
                                        title="{{ $parentText }}"
                                    >
                                        {{ $parentText }}
                                    </span>

                                    <span class="icon-chevron-right shrink-0 text-2xl text-gray-400 dark:text-gray-300"></span>
                                </div>
                            </x-slot>

                            <x-slot:header>
                                <p class="text-lg text-gray-800 dark:text-white font-bold">
                                    @lang('admin::app.catalog.categories.edit.select-parent-category')
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <label class="flex gap-2 items-center p-1.5 mb-1.5 border-b dark:border-cherry-800 cursor-pointer select-none">
                                    <input
                                        type="radio"
                                        name="parent_id"
                                        value=""
                                        class="hidden peer"
                                        @checked(! $parentLabel)
                                        @change="
                                            $refs.parentPathLabel.textContent = $refs.rootLevelLabel.textContent.trim();
                                            $refs.parentPathLabel.title = $refs.rootLevelLabel.textContent.trim();
                                            $refs.parentDrawer.close();
                                        "
                                    >

                                    <span class="icon-radio-normal text-2xl rounded-md peer-checked:icon-radio-selected peer-checked:text-primary-700"></span>

                                    <span class="text-sm text-gray-600 dark:text-gray-300" ref="rootLevelLabel">
                                        @lang('admin::app.catalog.categories.browse.root-level')
                                    </span>
                                </label>

                                <x-admin::tree.category.view
                                    input-type="radio"
                                    name-field="parent_id"
                                    label-field="name"
                                    value-field="id"
                                    id-field="id"
                                    children-page-size="100"
                                    ::show-search="true"
                                    ::show-toolbar="true"
                                    :current-category="$category?->id"
                                    :expanded-branch="json_encode($branchToParent)"
                                    :items="json_encode($treeItems)"
                                    :value="old('parent_id') ?? json_encode($category?->parent_id ?? $parentCategory?->id)"
                                    :fallback-locale="config('app.fallback_locale')"
                                    @select-node="
                                        $refs.parentPathLabel.textContent = $event.path;
                                        $refs.parentPathLabel.title = $event.path;
                                        $refs.parentDrawer.close();
                                    "
                                />
                            </x-slot>
                        </x-admin::drawer>
                    @else
                        <x-admin::form.control-group.control
                            type="text"
                            class="cursor-not-allowed"
                            name="parent_label"
                            disabled
                            :value="$parentText"
                        />
                    @endif
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

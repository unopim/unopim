@php
    $isEdit = (bool) $category;

    $currentLocale = core()->getRequestedLocale();

    $allActiveLocales = core()->getAllActiveLocales();

    $localeQuery = $isEdit
        ? ['category' => $category->id]
        : array_filter(['panel' => 'create', 'parent_id' => $parentCategory?->id]);
@endphp

<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <div class="flex gap-4 justify-between items-center pb-4 border-b dark:border-cherry-800 max-md:flex-wrap">
        <div class="flex flex-col gap-0.5 min-w-0">
            <p class="text-xs text-gray-400 dark:text-gray-300 truncate">
                {{ $breadcrumb ?: trans('admin::app.catalog.categories.browse.root-level') }}
            </p>

            <p class="text-base text-gray-800 dark:text-white font-semibold truncate">
                {{ $isEdit ? $category->name : trans('admin::app.catalog.categories.browse.new-category') }}
            </p>
        </div>

        <div class="flex gap-x-1 items-center shrink-0">
            <x-admin::dropdown :class="$allActiveLocales->count() <= 1 ? 'hidden' : ''">
                <x-slot:toggle>
                    <button
                        type="button"
                        class="flex gap-x-1 items-center px-3 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer appearance-none transition-all hover:!bg-primary-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50"
                    >
                        <span class="icon-language text-2xl"></span>

                        {{ $currentLocale->name }}

                        <span class="icon-chevron-down text-2xl"></span>
                    </button>
                </x-slot>

                <x-slot:content class="!p-0">
                    @foreach ($allActiveLocales as $locale)
                        <a
                            href="{{ route('admin.catalog.categories.index', $localeQuery + ['locale' => $locale->code]) }}"
                            class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800 dark:text-white {{ $locale->code == $currentLocale->code ? 'bg-gray-100 dark:bg-cherry-800' : ''}}"
                        >
                            {{ $locale->name }}
                        </a>
                    @endforeach
                </x-slot>
            </x-admin::dropdown>
        </div>
    </div>

    {!! view_render_event('unopim.admin.catalog.categories.panel.before', ['category' => $category]) !!}

    <x-admin::form
        id="category-panel-form"
        ajax
        :action="$isEdit ? route('admin.catalog.categories.update', $category->id) : route('admin.catalog.categories.store')"
        :method="$isEdit ? 'PUT' : 'POST'"
        enctype="multipart/form-data"
    >
        <x-admin::form.control-group.control
            type="hidden"
            name="locale"
            :value="$currentLocale->code"
        />

        <div class="flex flex-col gap-4 pt-4">
            <x-admin::form.control-group>
                <x-admin::form.control-group.label class="required">
                    @lang('admin::app.catalog.categories.create.code')
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

            @if (! $leftCategoryFields->isEmpty())
                <x-admin::categories.dynamic-fields
                    :fields="$leftCategoryFields"
                    :fieldValues="$isEdit ? $category->additional_data : []"
                />
            @endif

            @if (! $rightCategoryFields?->isEmpty())
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                            @lang('admin::app.catalog.categories.edit.right-section')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <x-admin::categories.dynamic-fields
                            :fields="$rightCategoryFields"
                            :fieldValues="$isEdit ? $category->additional_data : []"
                        />
                    </x-slot>
                </x-admin::accordion>
            @endif

            @if (count($treeItems))
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base text-gray-800 dark:text-white font-semibold">
                            @lang('admin::app.catalog.categories.edit.select-parent-category')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <div class="flex flex-col gap-3 max-h-[420px] overflow-y-auto">
                            <x-admin::tree.category.view
                                input-type="radio"
                                name-field="parent_id"
                                label-field="name"
                                value-field="id"
                                id-field="id"
                                children-page-size="100"
                                ::show-toolbar="true"
                                ::allow-create="{{ bouncer()->hasPermission('catalog.categories.create') ? 'true' : 'false' }}"
                                :current-category="$isEdit ? $category->id : null"
                                :expanded-branch="json_encode($branchToParent)"
                                :items="json_encode($treeItems)"
                                :value="old('parent_id') ?? json_encode($isEdit ? $category->parent_id : $parentCategory?->id)"
                                :fallback-locale="config('app.fallback_locale')"
                            />
                        </div>
                    </x-slot>
                </x-admin::accordion>
            @endif

            <div class="flex justify-end gap-2.5">
                <a
                    href="{{ route('admin.catalog.categories.index') }}"
                    class="transparent-button"
                >
                    @lang('admin::app.catalog.categories.create.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    {{ $isEdit
                        ? trans('admin::app.catalog.categories.edit.save-btn')
                        : trans('admin::app.catalog.categories.index.add-btn') }}
                </button>
            </div>
        </div>
    </x-admin::form>

    {!! view_render_event('unopim.admin.catalog.categories.panel.after', ['category' => $category]) !!}
</div>

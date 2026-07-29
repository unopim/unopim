@php
    $isEdit = (bool) $category;

    $currentLocale = core()->getRequestedLocale();

    $allActiveLocales = core()->getAllActiveLocales();

    $panelQuery = $isEdit
        ? ['category' => $category->id]
        : array_filter(['panel' => 'create', 'parent_id' => $parentCategory?->id]);

    $canSeeHistory = $isEdit && bouncer()->hasPermission('history');
@endphp

<div class="flex flex-col gap-2.5">
    <div class="flex gap-4 justify-between items-center max-md:flex-wrap">
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
                            href="{{ route('admin.catalog.categories.index', $panelQuery + ['locale' => $locale->code]) }}"
                            class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800 dark:text-white {{ $locale->code == $currentLocale->code ? 'bg-gray-100 dark:bg-cherry-800' : ''}}"
                        >
                            {{ $locale->name }}
                        </a>
                    @endforeach
                </x-slot>
            </x-admin::dropdown>
        </div>
    </div>

    @if ($canSeeHistory)
        <x-admin::layouts.edit-tabs
            class="!mt-0"
            :active="$showHistory ? 'history' : 'general'"
            :history-url="route('admin.catalog.categories.index', $panelQuery + ['history' => 1])"
            :show-history="true"
            :items="[[
                'key'   => 'general',
                'url'   => route('admin.catalog.categories.index', $panelQuery),
                'label' => 'admin::app.components.layouts.sidebar.general',
            ]]"
        />
    @endif

    @if ($canSeeHistory && $showHistory)
        {!! view_render_event('unopim.admin.layout.history.before') !!}

        <x-admin::history src="{{ route('admin.history.index', ['category', $category->id]) }}" />

        {!! view_render_event('unopim.admin.layout.history.after') !!}
    @else
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

            <x-admin::form.control-group.control
                type="hidden"
                name="parent_id"
                :value="$isEdit ? $category->parent_id : $parentCategory?->id"
            />

            @include('admin::catalog.categories.partials.form', ['showParent' => false])

            <div class="flex justify-end gap-2.5 mt-2.5">
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
        </x-admin::form>

        {!! view_render_event('unopim.admin.catalog.categories.panel.after', ['category' => $category]) !!}
    @endif
</div>

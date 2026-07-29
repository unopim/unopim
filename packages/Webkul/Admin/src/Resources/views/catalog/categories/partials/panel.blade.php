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
    <x-admin::layouts.edit-page-header
        :title="$isEdit
            ? trans('admin::app.catalog.categories.edit.title')
            : trans('admin::app.catalog.categories.create.title')"
        :back-url="route('admin.catalog.categories.index')"
        :back-label="trans('admin::app.catalog.categories.edit.back-btn')"
        form="category-panel-form"
        :sticky="false"
        :breadcrumb="false"
    />

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

    <div class="flex gap-4 justify-between items-center mt-2 max-md:flex-wrap">
        <div class="flex gap-x-1 items-center">
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

            @include('admin::catalog.categories.partials.form', [
                'showParent'   => false,
                'parentLabel'  => $breadcrumb,
                'parentPicker' => true,
            ])
        </x-admin::form>

        {!! view_render_event('unopim.admin.catalog.categories.panel.after', ['category' => $category]) !!}
    @endif
</div>

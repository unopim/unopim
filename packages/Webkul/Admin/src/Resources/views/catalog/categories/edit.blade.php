<x-admin::layouts.with-history>
    <x-slot:entityName>
        category
    </x-slot>
    
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.catalog.categories.edit.title')
    </x-slot>

    <x-slot:pageHeader>
        <x-admin::layouts.edit-page-header
            :title="trans('admin::app.catalog.categories.edit.title')"
            :back-url="route('admin.catalog.categories.index')"
            :back-label="trans('admin::app.catalog.categories.edit.back-btn')"
            form="category-edit-form"
            :sticky="false"
        />
    </x-slot>

    @php
        $currentLocale = core()->getRequestedLocale();

        $categoryCount = count($categories);

        $isEmptyRightSection = $rightCategoryFields?->isEmpty();
    @endphp

    {!! view_render_event('unopim.admin.catalog.categories.edit.before') !!}

    <!-- Category Edit Form -->
    <x-admin::form
        id="category-edit-form"
        ajax
        :action="route('admin.catalog.categories.update', $category->id)"
        enctype="multipart/form-data"
        method="PUT"
    >

        {!! view_render_event('unopim.admin.catalog.categories.edit.edit_form_controls.before', ['category' => $category]) !!}

        <!-- Filter Row -->
        <div class="flex  gap-4 justify-between items-center mt-2 max-md:flex-wrap">
            <div class="flex gap-x-1 items-center">
                <!-- Locale Switcher -->
                @php $allActiveLocales = core()->getAllActiveLocales(); @endphp

                <x-admin::dropdown :class="$allActiveLocales->count() <= 1 ? 'hidden' : ''">
                    <!-- Dropdown Toggler -->
                    <x-slot:toggle>
                        <button
                            type="button"
                            class="flex gap-x-1 items-center px-3 py-1.5 border-2 border-transparent rounded-md font-semibold whitespace-nowrap cursor-pointer marker:shadow appearance-none transition-all hover:!bg-primary-50 dark:hover:!bg-cherry-900 text-gray-600 dark:!text-slate-50"
                        >
                            <span class="icon-language text-2xl"></span>

                            {{ $currentLocale->name }}

                            <input type="hidden" name="locale" value="{{ $currentLocale->code }}"/>

                            <span class="icon-chevron-down text-2xl"></span>
                        </button>
                    </x-slot>

                    <!-- Dropdown Content -->
                    <x-slot:content class="!p-0">
                        @foreach ($allActiveLocales as $locale)
                            <a
                                href="?{{ Arr::query(['locale' => $locale->code]) }}"
                                class="flex gap-2.5 px-5 py-2 text-base cursor-pointer hover:bg-primary-50 dark:hover:bg-cherry-800 dark:text-white {{ $locale->code == $currentLocale->code ? 'bg-gray-100 dark:bg-cherry-800' : ''}}"
                            >
                                {{ $locale->name }}
                            </a>
                        @endforeach
                    </x-slot>
                </x-admin::dropdown>
            </div>
        </div>

        @include('admin::catalog.categories.partials.form', [
            'showParent' => true,
            'treeItems'  => $categories,
        ])

        {!! view_render_event('unopim.admin.catalog.categories.edit.edit_form_controls.after', ['category' => $category]) !!}

    </x-admin::form>

    {!! view_render_event('unopim.admin.catalog.categories.edit.after') !!}

</x-admin::layouts.with-history>

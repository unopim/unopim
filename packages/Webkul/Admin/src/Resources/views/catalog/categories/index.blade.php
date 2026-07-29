<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.categories.index.title')
    </x-slot>

    @php
        $isTreeView = $viewMode === 'tree';

        $canCreate = bouncer()->hasPermission('catalog.categories.create');
    @endphp

    <x-admin::page-header :title="trans('admin::app.catalog.categories.index.title')">
        <x-slot:actions>
            <div class="flex p-0.5 bg-gray-100 dark:bg-cherry-800 rounded-md">
                <a
                    href="{{ route('admin.catalog.categories.index', ['view' => 'tree']) }}"
                    class="px-3 py-1 text-sm rounded {{ $isTreeView ? 'bg-white dark:bg-cherry-900 text-gray-800 dark:text-white font-semibold' : 'text-gray-600 dark:text-gray-300' }}"
                >
                    @lang('admin::app.catalog.categories.browse.tree-view')
                </a>

                <a
                    href="{{ route('admin.catalog.categories.index', ['view' => 'list']) }}"
                    class="px-3 py-1 text-sm rounded {{ $isTreeView ? 'text-gray-600 dark:text-gray-300' : 'bg-white dark:bg-cherry-900 text-gray-800 dark:text-white font-semibold' }}"
                >
                    @lang('admin::app.catalog.categories.browse.list-view')
                </a>
            </div>

            {!! view_render_event('unopim.admin.catalog.categories.index.create-button.before') !!}

            @if ($canCreate)
                <a href="{{ route('admin.catalog.categories.index', ['panel' => 'create']) }}">
                    <div class="primary-button">
                        @lang('admin::app.catalog.categories.browse.add-root')
                    </div>
                </a>
            @endif

            {!! view_render_event('unopim.admin.catalog.categories.index.create-button.after') !!}
        </x-slot>
    </x-admin::page-header>

    @if (! $isTreeView)
        @include('admin::catalog.categories.partials.list')
    @else
        {!! view_render_event('unopim.admin.catalog.categories.browse.before') !!}

        <div class="flex gap-2.5 mt-3.5 flex-wrap">
            <div class="flex flex-col shrink-0 w-[360px] max-w-full p-4 h-[calc(100vh-170px)] bg-white dark:bg-cherry-900 rounded box-shadow">
                <div class="flex flex-col gap-3 h-full overflow-y-auto">
                    <x-admin::tree.category.view
                        input-type="radio"
                        name-field="browse_category"
                        label-field="name"
                        value-field="id"
                        id-field="id"
                        children-page-size="100"
                        ::show-toolbar="true"
                        ::show-search="true"
                        ::navigate-on-select="true"
                        ::allow-create="{{ $canCreate ? 'true' : 'false' }}"
                        ::allow-delete="{{ bouncer()->hasPermission('catalog.categories.delete') ? 'true' : 'false' }}"
                        :expanded-branch="json_encode($branchToParent)"
                        :items="json_encode($treeItems)"
                        :value="json_encode($selectedId)"
                        :fallback-locale="config('app.fallback_locale')"
                    />
                </div>
            </div>

            <div class="flex-1 min-w-0">
                @if ($panelMode)
                    @include('admin::catalog.categories.partials.panel')
                @else
                    <div class="flex flex-col gap-4 items-center justify-center p-8 h-[calc(100vh-170px)] bg-white dark:bg-cherry-900 rounded box-shadow">
                        <span class="icon-folder text-8xl text-gray-300 dark:text-gray-500"></span>

                        <p class="text-base text-gray-600 dark:text-gray-300">
                            @lang('admin::app.catalog.categories.browse.empty-panel')
                        </p>

                        @if ($canCreate)
                            <a href="{{ route('admin.catalog.categories.index', ['panel' => 'create']) }}">
                                <div class="secondary-button">
                                    @lang('admin::app.catalog.categories.browse.add-root')
                                </div>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {!! view_render_event('unopim.admin.catalog.categories.browse.after') !!}
    @endif
</x-admin::layouts>

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
            @php
                $activeToggle = 'px-3 py-1.5 text-sm rounded-md bg-white dark:bg-cherry-900 shadow-sm text-gray-800 dark:text-white font-semibold';

                $idleToggle = 'px-3 py-1.5 text-sm rounded-md text-gray-500 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white';
            @endphp

            <div class="flex gap-0.5 p-0.5 bg-gray-100 dark:bg-cherry-800 border border-gray-200 dark:border-cherry-800 rounded-md">
                <a
                    href="{{ route('admin.catalog.categories.index', ['view' => 'tree']) }}"
                    class="{{ $isTreeView ? $activeToggle : $idleToggle }}"
                >
                    @lang('admin::app.catalog.categories.browse.tree-view')
                </a>

                <a
                    href="{{ route('admin.catalog.categories.index', ['view' => 'list']) }}"
                    class="{{ $isTreeView ? $idleToggle : $activeToggle }}"
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
                <div class="flex flex-col h-full min-h-0">
                    <x-admin::tree.category.view
                        input-type="radio"
                        name-field="browse_category"
                        ::fill-height="true"
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
                    @include('admin::catalog.categories.partials.overview')
                @endif
            </div>
        </div>

        {!! view_render_event('unopim.admin.catalog.categories.browse.after') !!}
    @endif
</x-admin::layouts>

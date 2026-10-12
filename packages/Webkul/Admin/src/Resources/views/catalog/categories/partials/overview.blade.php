@php
    $roots = $overview['roots'];

    $channelRootIds = $overview['channelRootIds'];

    $canEditCategory = bouncer()->hasPermission('catalog.categories.edit');
@endphp

<div class="flex flex-col gap-4 p-6 bg-white dark:bg-cherry-900 rounded box-shadow">
    <div class="flex flex-col gap-1">
        <p class="text-2xl text-gray-800 dark:text-white font-bold">
            @lang('admin::app.catalog.categories.browse.categories-count', ['count' => number_format($overview['total'])])
        </p>

        <p class="text-sm text-gray-500 dark:text-gray-300">
            @lang('admin::app.catalog.categories.browse.roots-count', ['count' => number_format($roots->count())])
        </p>
    </div>

    @if ($roots->isNotEmpty())
        <div class="flex flex-col">
            <p class="pb-2 mb-1 border-b dark:border-cherry-800 text-xs text-gray-500 dark:text-gray-300 font-semibold uppercase">
                @lang('admin::app.catalog.categories.browse.trees')
            </p>

            @foreach ($roots as $root)
                @php $descendants = (int) (($root->_rgt - $root->_lft - 1) / 2); @endphp

                <a
                    @if ($canEditCategory)
                        href="{{ route('admin.catalog.categories.index', ['category' => $root->id]) }}"
                    @endif
                    class="flex gap-2.5 items-center px-2 py-2.5 border-b dark:border-cherry-800 rounded-md {{ $canEditCategory ? 'hover:bg-primary-50 dark:hover:bg-cherry-800' : '' }}"
                >
                    <span class="icon-folder shrink-0 text-2xl text-gray-500 dark:text-gray-300"></span>

                    <span class="flex-1 text-sm text-gray-800 dark:text-white truncate">
                        {{ $root->name }}
                    </span>

                    @if (in_array((int) $root->id, $channelRootIds, true))
                        <span class="shrink-0 px-2 py-0.5 bg-primary-50 dark:bg-cherry-800 rounded text-xs text-primary-600 dark:text-gray-300">
                            @lang('admin::app.catalog.categories.browse.channel-root')
                        </span>
                    @endif

                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-300">
                        @lang('admin::app.catalog.categories.browse.subcategories-count', ['count' => number_format($descendants)])
                    </span>

                    @if ($canEditCategory)
                        <span class="icon-chevron-right shrink-0 text-2xl text-gray-400 dark:text-gray-300"></span>
                    @endif
                </a>
            @endforeach
        </div>
    @else
        <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-300">
            @lang('admin::app.catalog.categories.browse.empty')
        </p>

        @if ($canCreate)
            <a
                class="self-center"
                href="{{ route('admin.catalog.categories.index', ['panel' => 'create']) }}"
            >
                <div class="secondary-button">
                    @lang('admin::app.catalog.categories.browse.add-root')
                </div>
            </a>
        @endif
    @endif
</div>

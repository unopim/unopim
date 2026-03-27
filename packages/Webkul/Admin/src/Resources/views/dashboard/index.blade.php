<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.dashboard.index.title')
    </x-slot>

    <!-- Welcome Banner -->
    <div class="flex gap-4 justify-between items-center mb-5 max-sm:flex-wrap">
        <div class="grid gap-1.5">
            <p class="text-xl text-zinc-800 dark:text-slate-50 font-bold !leading-normal">
                @lang('admin::app.dashboard.index.user-name', ['user_name' => auth()->guard('admin')->user()->name]),
            </p>

            <p class="text-sm text-zinc-600 !leading-normal dark:text-slate-300">
                @lang('admin::app.dashboard.index.user-info')
            </p>
        </div>

        <!-- Quick Actions -->
        <div class="flex gap-2 max-sm:flex-wrap">
            @if (bouncer()->hasPermission('catalog.products.create'))
                <a
                    href="{{ route('admin.catalog.products.index') }}"
                    class="primary-button text-xs no-underline"
                >
                    <span class="icon-add text-sm"></span>
                    @lang('admin::app.dashboard.index.create-product')
                </a>
            @endif

            @if (bouncer()->hasPermission('data_transfer.imports'))
                <a
                    href="{{ route('admin.settings.data_transfer.imports.index') }}"
                    class="secondary-button text-xs no-underline"
                >
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    @lang('admin::app.dashboard.index.import-data')
                </a>
            @endif

            @if (bouncer()->hasPermission('data_transfer.export'))
                <a
                    href="{{ route('admin.settings.data_transfer.exports.index') }}"
                    class="secondary-button text-xs no-underline"
                >
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="transform: rotate(180deg); transform-origin: center;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    @lang('admin::app.dashboard.index.export-data')
                </a>
            @endif
        </div>
    </div>

    <!-- ═══ OVERVIEW ═══ -->

    <!-- Catalog Overview -->
    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
        <div class="flex flex-col gap-2 w-full">
            <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
                @lang('admin::app.dashboard.index.catalog-details')
            </p>

            @include('admin::dashboard.total-catalogs')
        </div>
    </div>

    <!-- Catalog Structure -->
    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
        <div class="flex flex-col gap-2 w-full">
            <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
                @lang('admin::app.dashboard.index.settings-details')
            </p>

            @include('admin::dashboard.total-catalog-structure')
        </div>
    </div>

    <!-- Needs Attention -->
    @include('admin::dashboard.needs-attention')

    <!-- ═══ ANALYTICS ═══ -->
    <div class="flex items-center gap-3 mt-8 mb-4">
        <p class="text-xs font-semibold text-zinc-400 dark:text-slate-500 uppercase tracking-widest">
            @lang('admin::app.dashboard.index.analytics-section')
        </p>
        <div class="flex-1 border-t border-zinc-200 dark:border-cherry-800"></div>
    </div>

    <!-- Product Statistics & Trend -->
    <div class="flex items-stretch gap-4 max-xl:flex-wrap">
        <div class="flex flex-col gap-2 flex-1 min-w-[300px]">
            <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
                @lang('admin::app.dashboard.index.product-stats')
            </p>

            @include('admin::dashboard.product-stats')
        </div>

        <div class="flex flex-col gap-2 flex-1 min-w-[300px]">
            <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
                @lang('admin::app.dashboard.index.products-over-time')
            </p>

            @include('admin::dashboard.product-trend')
        </div>
    </div>

    <!-- Completeness -->
    <div class="flex gap-2.5 mt-5 max-xl:flex-wrap">
        <div class="flex flex-col gap-2 w-full">
            <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
                @lang('admin::app.dashboard.index.completeness')
            </p>

            @includeIf('completeness::dashboard.index')
        </div>
    </div>

    <!-- Channel Readiness -->
    <div class="flex gap-2.5 mt-5 max-xl:flex-wrap">
        <div class="flex flex-col gap-2 w-full">
            <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
                @lang('admin::app.dashboard.index.channel-readiness')
            </p>

            @include('admin::dashboard.channel-readiness')
        </div>
    </div>

    <!-- ═══ OPERATIONS ═══ -->
    <div class="flex items-center gap-3 mt-8 mb-4">
        <p class="text-xs font-semibold text-zinc-400 dark:text-slate-500 uppercase tracking-widest">
            @lang('admin::app.dashboard.index.operations-section')
        </p>
        <div class="flex-1 border-t border-zinc-200 dark:border-cherry-800"></div>
    </div>

    <!-- Recent Activity & Data Transfer -->
    <div class="flex items-stretch gap-4 max-xl:flex-wrap">
        <div class="flex flex-col gap-2 flex-1 min-w-[300px]">
            <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
                @lang('admin::app.dashboard.index.recent-activity')
            </p>

            @include('admin::dashboard.recent-activity')
        </div>

        <div class="flex flex-col gap-2 flex-1 min-w-[300px]">
            <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
                @lang('admin::app.dashboard.index.data-transfer')
            </p>

            @include('admin::dashboard.data-transfer')
        </div>
    </div>
</x-admin::layouts>

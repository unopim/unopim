<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.dashboard.index.title')
    </x-slot>

    <!-- User Details Section -->
    <div class="flex gap-4 justify-between items-center mb-5 max-sm:flex-wrap">
        <div class="grid gap-1.5">
            <p class="text-xl text-zinc-800 dark:text-slate-50 font-bold !leading-normal">
                @lang('admin::app.dashboard.index.user-name', ['user_name' => auth()->guard('admin')->user()->name]),
            </p>

            <p class="text-sm text-zinc-600 !leading-normal dark:text-slate-300">
                @lang('admin::app.dashboard.index.user-info')
            </p>
        </div>
    </div>

    <!-- Body Component -->
    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
        <!-- Catalog Details -->
        <div class="flex flex-col gap-2 w-full">
            <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
                @lang('admin::app.dashboard.index.catalog-details')
            </p>

            <!-- Catalog Details Section -->
            @include('admin::dashboard.total-catalogs')
        </div>
    </div>

    <div class="flex gap-2.5 mt-3.5 max-xl:flex-wrap">
        <!-- Catalog Structure Details -->
        <div class="flex flex-col gap-2 w-full">
            <p class="text-base text-zinc-800 dark:text-slate-50 font-bold">
                @lang('admin::app.dashboard.index.settings-details')
            </p>

            <!-- Catalog Structure Section -->
            @include('admin::dashboard.total-catalog-structure')
        </div>
    </div>
</x-admin::layouts>

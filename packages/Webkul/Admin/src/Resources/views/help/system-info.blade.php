<x-admin::layouts>
    <x-slot:title>
        {{ trans('admin::app.help.system-info.title') }}
    </x-slot>

    {{-- Page header --}}
    <div class="flex flex-col gap-1 mt-3.5">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            {{ trans('admin::app.help.system-info.title') }}
        </p>

        <p class="text-gray-600 dark:text-gray-300 leading-[140%] max-w-[720px]">
            {{ trans('admin::app.help.system-info.subtitle') }}
        </p>
    </div>

    @php
        $sectionIcons = [
            'application' => 'icon-dashboard',
            'server'      => 'icon-configuration',
            'database'    => 'icon-folder-block',
            'services'    => 'icon-setting',
        ];
    @endphp

    {{-- Grouped key/value sections --}}
    <div class="grid grid-cols-1 gap-4 mt-5 md:grid-cols-2">
        @foreach ($sections as $sectionKey => $rows)
            <div class="flex flex-col bg-white dark:bg-cherry-900 rounded-lg box-shadow p-4">
                <div class="flex items-center gap-3 pb-3 mb-2 border-b dark:border-cherry-800">
                    <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-300 text-xl {{ $sectionIcons[$sectionKey] ?? 'icon-information' }}"></span>

                    <p class="text-base font-semibold text-gray-800 dark:text-slate-50">
                        {{ trans('admin::app.help.system-info.sections.'.$sectionKey) }}
                    </p>
                </div>

                <dl class="text-sm">
                    @foreach ($rows as $field => $value)
                        <div class="flex items-start justify-between gap-4 py-2.5 px-2.5 rounded-md odd:bg-gray-50 dark:odd:bg-cherry-800/40">
                            <dt class="text-gray-500 dark:text-gray-400 font-medium shrink-0">
                                {{ trans('admin::app.help.system-info.fields.'.$field) }}
                            </dt>

                            <dd class="text-gray-800 dark:text-slate-50 font-semibold text-right break-all">
                                {{ $value }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endforeach
    </div>

    {{-- PHP extensions --}}
    @php
        $loadedExtensions = collect($extensions)->filter()->count();
    @endphp

    <div class="flex flex-col bg-white dark:bg-cherry-900 rounded-lg box-shadow p-4 mt-4">
        <div class="flex items-center gap-3 pb-3 mb-3 border-b dark:border-cherry-800">
            <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-300 text-xl icon-configuration"></span>

            <p class="text-base font-semibold text-gray-800 dark:text-slate-50">
                {{ trans('admin::app.help.system-info.sections.extensions') }}
            </p>

            <x-admin::badge variant="neutral">
                {{ $loadedExtensions }}/{{ count($extensions) }}
            </x-admin::badge>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach ($extensions as $extension => $loaded)
                <x-admin::badge :variant="$loaded ? 'success' : 'danger'">
                    <span class="w-1.5 h-1.5 rounded-full {{ $loaded ? 'bg-green-500' : 'bg-red-500' }}"></span>
                    {{ $extension }}
                </x-admin::badge>
            @endforeach
        </div>
    </div>

    {{-- Installed packages --}}
    <div class="bg-white dark:bg-cherry-900 rounded-lg box-shadow p-4 mt-4">
        <v-system-packages :packages='@json($packages)'></v-system-packages>
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-system-packages-template"
        >
            <div class="flex flex-col">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 mb-3 border-b dark:border-cherry-800">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-9 h-9 rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-300 text-xl icon-catalog"></span>

                        <p class="text-base font-semibold text-gray-800 dark:text-slate-50">
                            @lang('admin::app.help.system-info.sections.packages')
                        </p>

                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium leading-none bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            @{{ filteredCount }}
                        </span>
                    </div>

                    <div class="flex items-center w-full max-w-xs border rounded-md overflow-hidden text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 focus-within:border-gray-400 dark:bg-cherry-900 dark:border-gray-600">
                        <span class="icon-search text-xl ltr:pl-2 rtl:pr-2 text-gray-500"></span>

                        <input
                            type="text"
                            v-model="query"
                            class="w-full px-2 py-1.5 bg-transparent outline-none"
                            placeholder="@lang('admin::app.help.system-info.search-packages')"
                        />
                    </div>
                </div>

                <div
                    v-if="filteredCount"
                    class="grid gap-x-6 gap-y-1 sm:grid-cols-2 xl:grid-cols-3"
                >
                    <div
                        v-for="pkg in filtered"
                        :key="pkg.name"
                        class="flex items-center justify-between gap-3 py-2 px-2.5 rounded-md hover:bg-gray-50 dark:hover:bg-cherry-800/40 border-b border-gray-100 dark:border-cherry-800"
                    >
                        <span class="text-gray-700 dark:text-slate-100 break-all">@{{ pkg.name }}</span>

                        <span class="text-gray-500 dark:text-gray-400 font-medium shrink-0">@{{ pkg.version }}</span>
                    </div>
                </div>

                <p
                    v-else
                    class="text-sm text-gray-500 dark:text-gray-400 py-6 text-center"
                >
                    @lang('admin::app.help.system-info.no-packages')
                </p>
            </div>
        </script>

        <script type="module">
            app.component('v-system-packages', {
                template: '#v-system-packages-template',

                props: ['packages'],

                data() {
                    return {
                        query: '',
                    };
                },

                computed: {
                    list() {
                        return Object.entries(this.packages ?? {}).map(([name, version]) => ({ name, version }));
                    },

                    filtered() {
                        const query = this.query.trim().toLowerCase();

                        if (! query) {
                            return this.list;
                        }

                        return this.list.filter((pkg) => pkg.name.toLowerCase().includes(query));
                    },

                    filteredCount() {
                        return this.filtered.length;
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>

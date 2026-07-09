<x-admin::layouts>
    <x-slot:title>@lang('admin::app.settings.system-settings.title')</x-slot>

    {!! view_render_event('unopim.admin.system_settings.index.before') !!}

    {{-- Page header --}}
    <div class="flex flex-col gap-1 mt-3.5">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('admin::app.settings.system-settings.title')
        </p>

        <p class="text-gray-600 dark:text-gray-300 leading-[140%] max-w-[720px]">
            @lang('admin::app.settings.system-settings.info')
        </p>
    </div>

    {{-- Client-side filter over the registered rows --}}
    <div class="mt-5 max-w-[720px]">
        <x-admin::form.control-group.control
            type="text"
            name="settings_search"
            data-settings-search
            :placeholder="trans('admin::app.settings.system-settings.search-placeholder')"
        />
    </div>

    <div class="grid gap-4 mt-5" data-settings-list>
        @foreach ($tree->items as $section)
            <x-admin::settings.section
                :title="trans($section['name'])"
                :info="isset($section['info']) ? trans($section['info']) : ''"
                :key="$section['key']"
            >
                @foreach ($section['children'] ?? [] as $row)
                    <x-admin::settings.row
                        :title="trans($row['name'])"
                        :info="isset($row['info']) ? trans($row['info']) : ''"
                        :icon="$row['icon'] ?? null"
                        :href="isset($row['route']) ? route($row['route']) : route('admin.settings.system.edit', $row['key'])"
                    />
                @endforeach
            </x-admin::settings.section>
        @endforeach
    </div>

    {!! view_render_event('unopim.admin.system_settings.index.after') !!}

    @push('scripts')
        <script>
            (() => {
                const input = document.querySelector('input[data-settings-search]');

                if (! input) {
                    return;
                }

                input.addEventListener('input', () => {
                    const query = input.value.trim().toLowerCase();

                    document.querySelectorAll('[data-settings-section]').forEach((section) => {
                        let anyVisible = false;

                        section.querySelectorAll('[data-settings-row]').forEach((row) => {
                            const match = row.getAttribute('data-search').includes(query);

                            row.style.display = match ? '' : 'none';

                            if (match) {
                                anyVisible = true;
                            }
                        });

                        section.style.display = anyVisible ? '' : 'none';
                    });
                });
            })();
        </script>
    @endpush
</x-admin::layouts>

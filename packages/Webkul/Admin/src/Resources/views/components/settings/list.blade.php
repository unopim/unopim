@props([
    'tree',
])

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

    {{-- Empty state, shown by the search filter when nothing matches. --}}
    <div
        data-settings-empty
        class="hidden bg-white dark:bg-cherry-800 rounded-2xl border border-gray-100 dark:border-cherry-700 p-8 text-center text-gray-500 dark:text-gray-400"
    >
        @lang('admin::app.components.datagrid.table.no-records-available')
    </div>
</div>

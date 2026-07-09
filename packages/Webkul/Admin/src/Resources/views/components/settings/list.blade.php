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
</div>

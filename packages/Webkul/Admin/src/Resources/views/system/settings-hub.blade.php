<x-admin::layouts>
    <x-slot:title>@lang('admin::app.settings.system-settings.title')</x-slot>

    {{-- Full hub markup (section cards + rows + live search) lands in Task 3. --}}
    @foreach ($tree->items as $section)
        <p>@lang($section['name'])</p>

        @foreach ($section['children'] ?? [] as $row)
            <a href="{{ isset($row['route']) ? route($row['route']) : route('admin.settings.system.edit', $row['key']) }}">
                @lang($row['name'])
            </a>
        @endforeach
    @endforeach
</x-admin::layouts>

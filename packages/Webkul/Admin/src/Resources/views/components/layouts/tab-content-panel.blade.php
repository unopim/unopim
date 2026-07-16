@props([
    'title',
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'tab-content-panel grid gap-4 mt-3.5']) }}>
    <x-admin::layouts.page-header
        :title="$title"
        :description="$description"
    >
        @isset($actions)
            <x-slot:actions>
                {{ $actions }}
            </x-slot>
        @endisset
    </x-admin::layouts.page-header>

    {{ $slot }}
</section>

@props([
    'title'      => null,
    'subtitle'   => '',
    'size'       => 'xl',
    'breadcrumb' => true,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }}>
    @if ($breadcrumb)
        <x-admin::breadcrumbs :leaf="$title" />
    @endif

    @isset($heading)
        <x-admin::heading :subtitle="$subtitle" as="h1" :size="$size">{{ $heading }}</x-admin::heading>
    @else
        <x-admin::heading :title="$title" :subtitle="$subtitle" as="h1" :size="$size" />
    @endisset

    {{ $slot }}
</div>

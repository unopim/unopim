@props([
    'title',
    'subtitle' => '',
    'back'     => null,
    'action'   => null,
    'method'   => 'POST',
    'ajax'     => false,
    'enctype'  => null,
])

{{--
    Standard admin page shell — the single template every page uses so the frame
    (layout + header + content spacing) stays consistent and pages never hand-write
    wrapper divs. Supply `title` (+ optional `subtitle`/`back`), an `actions` slot
    for header buttons, and the page body as the default slot. When `action` is set
    the whole page is wrapped in an ajax-capable form so header submit buttons post
    the body. The header uses x-admin::page-header; content sits below with a
    consistent top gap.
--}}
<x-admin::layouts>
    <x-slot:title>{{ $title }}</x-slot>

    @if ($action)
        <x-admin::form
            :action="$action"
            :method="$method"
            :ajax="$ajax"
            enctype="{{ $enctype }}"
        >
            <x-admin::page-header :title="$title" :subtitle="$subtitle" :back="$back">
                @isset($actions)
                    <x-slot:actions>{{ $actions }}</x-slot>
                @endisset
            </x-admin::page-header>

            <div class="mt-5">{{ $slot }}</div>
        </x-admin::form>
    @else
        <x-admin::page-header :title="$title" :subtitle="$subtitle" :back="$back">
            @isset($actions)
                <x-slot:actions>{{ $actions }}</x-slot>
            @endisset
        </x-admin::page-header>

        <div class="mt-5">{{ $slot }}</div>
    @endif
</x-admin::layouts>

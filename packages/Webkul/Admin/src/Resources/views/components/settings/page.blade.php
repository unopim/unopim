@props([
    'title',
    'info'    => '',
    'back'    => null,
    'action'  => null,
    'method'  => 'POST',
    'ajax'    => false,
    'enctype' => null,
])

{{--
    Standard settings page template: owns the UX frame (layout + header with
    title/info/optional back + actions area + content). Pages only supply the
    title/back, an `actions` slot (buttons top-right) and their content.
    When `action` is set the whole page is wrapped in an ajax-capable form, so
    submit buttons placed in the `actions` slot post the content below.
--}}
<x-admin::layouts>
    <x-slot:title>{{ $title }}</x-slot>

    @if ($action)
        {{-- track-dirty defaults to true (form default), so saving flows through the
             global unsaved-changes bar — same as every other admin edit page. --}}
        <x-admin::form
            :action="$action"
            :method="$method"
            :ajax="$ajax"
            enctype="{{ $enctype }}"
        >
            <x-admin::settings.page-header :title="$title" :info="$info" :back="$back">
                @isset($actions)
                    <x-slot:actions>{{ $actions }}</x-slot>
                @endisset
            </x-admin::settings.page-header>

            {{ $slot }}
        </x-admin::form>
    @else
        <x-admin::settings.page-header :title="$title" :info="$info" :back="$back">
            @isset($actions)
                <x-slot:actions>{{ $actions }}</x-slot>
            @endisset
        </x-admin::settings.page-header>

        {{ $slot }}
    @endif
</x-admin::layouts>

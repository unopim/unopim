@php
    $active ??= '';

    $tabs = [];

    if (bouncer()->hasPermission('catalog.passport.view')) {
        $tabs['passports'] = [
            'title' => trans('passport::app.components.layouts.sidebar.menu.passports.name'),
            'url'   => route('admin.catalog.passports.index'),
        ];
    }

    if (bouncer()->hasPermission('catalog.passport.mapping')) {
        $tabs['mapping'] = [
            'title' => trans('passport::app.mapping.menu'),
            'url'   => route('admin.catalog.passports.mapping.edit'),
        ];
    }
@endphp

{{-- Route-driven tabs: the two passport surfaces share one visual tab bar, each a
     link to its own route so the breadcrumb and permissions resolve per page. The
     bar is hidden when the admin can reach only one surface. --}}
@if (count($tabs) > 1)
    <div class="flex gap-4 pt-2 mb-5 border-b dark:border-gray-800">
        @foreach ($tabs as $key => $tab)
            <a
                href="{{ $tab['url'] }}"
                @class([
                    'pb-3.5 px-2.5 text-base font-medium cursor-pointer transition-all border-b-2',
                    'border-primary-700 text-primary-700 dark:text-primary-400' => $active === $key,
                    'border-transparent text-gray-600 dark:text-gray-300 hover:text-primary-700' => $active !== $key,
                ])
            >
                {{ $tab['title'] }}
            </a>
        @endforeach
    </div>
@endif

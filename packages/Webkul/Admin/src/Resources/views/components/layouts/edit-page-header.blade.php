@props([
    'title',
    'backUrl' => null,
    'backLabel' => trans('admin::app.account.edit.back-btn'),
    'saveLabel' => null,
    'form' => null,
    'sticky' => true,
    'breadcrumb' => true,
])

@php
    $stickyClasses = $sticky ? 'js-sticky-header sticky -top-3 z-[9999] -mx-4 -mt-3 border-b border-gray-200 bg-unopim-primary-page px-4 py-2 transition-shadow dark:border-gray-800 dark:bg-cherry-800' : '';
    $headerAttributes = $attributes->merge(['class' => $stickyClasses]);

    if (request()->has('history') && bouncer()->hasPermission('history')) {
        $saveLabel = null;
    }

    $saveAttributes = new \Illuminate\View\ComponentAttributeBag([
        'type'  => 'submit',
        'class' => 'primary-button',
    ]);

    if ($form) {
        $saveAttributes = $saveAttributes->merge(['form' => $form]);
    }
@endphp

<div {{ $headerAttributes->merge(['class' => 'flex min-h-9 items-center justify-between gap-4 max-sm:flex-wrap']) }}>
    <x-admin::page-title :title="$title" :breadcrumb="$breadcrumb">
        {{ $subtitle ?? '' }}
    </x-admin::page-title>

    <div class="flex items-center gap-2.5">
        {{ $beforeActions ?? '' }}

        @if ($backUrl)
            <a
                href="{{ $backUrl }}"
                class="transparent-button"
            >
                {{ $backLabel }}
            </a>
        @endif

        {{ $actions ?? '' }}

        @if ($saveLabel)
            <button {{ $saveAttributes }}>
                {{ $saveLabel }}
            </button>
        @endif
    </div>
</div>

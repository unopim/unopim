@props([
    'title',
    'backUrl' => null,
    'backLabel' => trans('admin::app.account.edit.back-btn'),
    'saveLabel' => null,
    'form' => null,
    'sticky' => true,
])

@php
    $stickyClasses = $sticky ? 'js-sticky-header sticky top-[57px] z-20 -mx-4 -mt-3 border-b border-gray-200 bg-unopim-primary-page px-4 py-2 transition-shadow dark:border-gray-800 dark:bg-cherry-800' : '';
    $headerAttributes = $attributes->merge(['class' => $stickyClasses]);

    $saveAttributes = new \Illuminate\View\ComponentAttributeBag([
        'type'  => 'submit',
        'class' => 'primary-button',
    ]);

    if ($form) {
        $saveAttributes = $saveAttributes->merge(['form' => $form]);
    }
@endphp

<div {{ $headerAttributes->merge(['class' => 'flex min-h-9 items-center justify-between gap-4 max-sm:flex-wrap']) }}>
    <div class="grid gap-1">
        <p class="text-xl font-bold leading-6 text-gray-800 dark:text-slate-50">
            {{ $title }}
        </p>

        {{ $subtitle ?? '' }}
    </div>

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

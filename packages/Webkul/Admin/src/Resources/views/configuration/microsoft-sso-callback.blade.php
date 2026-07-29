@php
    $callbackUrl = route('admin.session.sso.callback', ['provider' => 'microsoft']);
@endphp

<x-admin::form.control-group class="!mb-4">
    <x-admin::form.control-group.label>
        @lang('admin::app.configuration.index.general.microsoft-sso.settings.redirect-uri')
    </x-admin::form.control-group.label>

    <div class="relative w-full">
        <input
            type="text"
            class="min-h-[39px] w-full py-2 ltr:pr-10 rtl:pl-10 px-3 border dark:border-cherry-800 rounded-md text-sm text-gray-600 dark:text-gray-300 font-normal bg-gray-50 dark:bg-cherry-900"
            value="{{ $callbackUrl }}"
            readonly
            onclick="this.select()"
            aria-label="@lang('admin::app.configuration.index.general.microsoft-sso.settings.redirect-uri')"
        />

        <span
            class="icon-copy text-2xl cursor-pointer absolute top-2/4 -translate-y-2/4 ltr:right-2 rtl:left-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-200"
            role="button"
            tabindex="0"
            onclick="copyMicrosoftSsoRedirectUri(this)"
            title="@lang('admin::app.configuration.index.general.microsoft-sso.settings.redirect-uri-copy')"
            aria-label="@lang('admin::app.configuration.index.general.microsoft-sso.settings.redirect-uri-copy')"
            data-copied-title="@lang('admin::app.configuration.index.general.microsoft-sso.settings.redirect-uri-copied')"
        >
        </span>
    </div>

    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        @lang('admin::app.configuration.index.general.microsoft-sso.settings.redirect-uri-info')
    </p>
</x-admin::form.control-group>

@pushOnce('scripts')
    <script>
        function copyMicrosoftSsoRedirectUri(trigger) {
            const field = trigger.parentElement.querySelector('input');

            field.select();

            navigator.clipboard.writeText(field.value).then(() => {
                const originalTitle = trigger.getAttribute('title');

                trigger.classList.replace('icon-copy', 'icon-done');

                trigger.setAttribute('title', trigger.dataset.copiedTitle);

                setTimeout(() => {
                    trigger.classList.replace('icon-done', 'icon-copy');

                    trigger.setAttribute('title', originalTitle);
                }, 2000);
            });
        }
    </script>
@endPushOnce

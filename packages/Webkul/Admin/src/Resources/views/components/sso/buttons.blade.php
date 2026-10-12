@props(['providers'])

@if (! empty($providers) && count($providers))
    {!! view_render_event('unopim.admin.sessions.create.sso.before', ['providers' => $providers]) !!}

    <div class="relative mt-6 mb-6">
        <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <span class="w-full border-t border-gray-200 dark:border-cherry-700"></span>
        </div>

        <div class="relative flex justify-center">
            <span class="px-3 bg-white dark:bg-cherry-800 text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">
                @lang('admin::app.users.sessions.sso-divider')
            </span>
        </div>
    </div>

    <div class="flex flex-col gap-3">
        @foreach ($providers as $provider)
            <x-admin::sso.button :provider="$provider" />
        @endforeach
    </div>

    {!! view_render_event('unopim.admin.sessions.create.sso.after', ['providers' => $providers]) !!}
@endif

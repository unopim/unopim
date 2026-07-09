<x-admin::settings.page
    :title="trans('admin::app.settings.system-settings.title')"
    :info="trans('admin::app.settings.system-settings.info')"
>
    {!! view_render_event('unopim.admin.system_settings.index.before') !!}

    <x-admin::settings.search />

    <x-admin::settings.list :tree="$tree" />

    {!! view_render_event('unopim.admin.system_settings.index.after') !!}
</x-admin::settings.page>

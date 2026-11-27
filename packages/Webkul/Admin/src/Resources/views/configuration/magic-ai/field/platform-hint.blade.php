@if (empty($platforms))
    <p class="mt-1 text-xs text-warning">
        @lang('admin::app.configuration.platform.setup.no-platform-hint')
    </p>
@else
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        @lang('admin::app.configuration.platform.fields.use-default-hint')
    </p>
@endif

<x-admin::layouts>
    <x-slot:title>
        @lang('webhook::app.webhooks.create.title')
    </x-slot>

    <x-admin::form :action="route('webhook.store')">
        <x-admin::page-header :title="trans('webhook::app.webhooks.create.title')">
            <x-slot:actions>
                <a
                    href="{{ route('webhook.index') }}"
                    class="transparent-button"
                >
                    @lang('webhook::app.webhooks.create.cancel')
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('webhook::app.webhooks.create.save-btn')
                </button>
            </x-slot>
        </x-admin::page-header>

        @php($webhook = null)

        @include('webhook::webhooks._fields')
    </x-admin::form>
</x-admin::layouts>

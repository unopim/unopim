<x-admin::layouts>
    <x-slot:title>
        @lang('order::app.admin.profitability.by-product.title')
    </x-slot>

    {!! view_render_event('unopim.order.profitability.by-product.before') !!}

    <div class="flex justify-between items-center mb-4">
        <p class="text-xl text-gray-800 dark:text-slate-50 font-bold">
            @lang('order::app.admin.profitability.by-product.page-title')
        </p>

        <div class="flex gap-x-2.5 items-center">
            <!-- Back Button -->
            <a
                href="{{ route('admin.order.profitability.index') }}"
                class="transparent-button"
            >
                @lang('order::app.admin.profitability.by-product.back')
            </a>

            <!-- Export Button -->
            @if (bouncer()->hasPermission('order.profitability.export'))
                <a
                    href="{{ route('admin.order.profitability.export', ['type' => 'by-product']) }}"
                    class="secondary-button"
                >
                    @lang('order::app.admin.profitability.by-product.export')
                </a>
            @endif
        </div>
    </div>

    <!-- Product Profitability DataGrid -->
    {!! view_render_event('unopim.order.profitability.by-product.datagrid.before') !!}

    <x-admin::datagrid src="{{ route('admin.order.profitability.by-product') }}" />

    {!! view_render_event('unopim.order.profitability.by-product.datagrid.after') !!}

    {!! view_render_event('unopim.order.profitability.by-product.after') !!}

</x-admin::layouts>

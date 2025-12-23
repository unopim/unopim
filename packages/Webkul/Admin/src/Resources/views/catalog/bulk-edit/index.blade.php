<x-admin::layouts>
    <!-- Title of the page -->
    <x-slot:title>
        @lang('admin::app.catalog.products.bulk-edit.action')
    </x-slot>

    <x-admin::bulkedit.editor
        :columns="$columns"
        :rows="$rows"
    >
    </x-admin::bulkedit.editor>

</x-admin::layouts>

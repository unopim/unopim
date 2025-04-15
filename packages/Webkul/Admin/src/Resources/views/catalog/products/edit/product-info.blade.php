@props([
    'product' => $product,
])

<!-- Panel -->
<div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
    <!-- Panel Header -->
    <p class="flex justify-between text-base text-gray-800 dark:text-white font-semibold mb-4">
        @lang('admin::app.catalog.products.edit.product-info.title')
    </p>

    <!-- Panel Content -->
    <div class="mb-5 text-sm text-gray-600 dark:text-gray-300">

        <!-- Status Switch -->
        <x-admin::form.control-group>
            <x-admin::form.control-group.label>
                @lang('admin::app.catalog.products.edit.product-info.status')
            </x-admin::form.control-group.label>
            <input type="hidden" name="status" value="0" />
            <x-admin::form.control-group.control
                type="switch"
                name="status"
                value="1"
                :checked="1 == (old('status') ?? $product->status ?? 1)"
                :label="trans('admin::app.catalog.products.edit.product-info.status')"
            />
        </x-admin::form.control-group>

        <!-- Attribute Family -->
        <div class="mb-4">
            <label class="flex gap-1 items-center mb-1.5 text-xs text-gray-800 dark:text-white font-medium">
                @lang('Family')
            </label>
            <div class="w-full py-2.5 px-3 border rounded-md text-sm bg-gray-100 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-600">
                {{ $product->attribute_family_id ? optional($product->attribute_family)->code : '—' }}
            </div>
        </div>

        <!-- Product Type -->
        <div class="mb-4">
            <label class="flex gap-1 items-center mb-1.5 text-xs text-gray-800 dark:text-white font-medium">
                @lang('admin::app.catalog.products.edit.product-info.product-type')
            </label>
            <div class="w-full py-2.5 px-3 border rounded-md text-sm bg-gray-100 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-600">
                {{ $product->type ?? '—' }}
            </div>
        </div>

        <!-- Parent Product (if exists) -->
        @if ($product->parent_id)
            <div class="mb-4">
                <label class="flex gap-1 items-center mb-1.5 text-xs text-gray-800 dark:text-white font-medium">
                    @lang('admin::app.catalog.products.edit.product-info.parent')
                </label>
                <div class="w-full py-2.5 px-3 border rounded-md text-sm bg-gray-100 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-600">
                    {{ optional($product->parent)->sku ?? '—' }}
                </div>
            </div>
        @endif

        <!-- Updated At -->
        <div class="mb-4">
            <label class="flex gap-1 items-center mb-1.5 text-xs text-gray-800 dark:text-white font-medium">
                @lang('admin::app.catalog.products.edit.product-info.updated-at')
            </label>
            <div class="w-full py-2.5 px-3 border rounded-md text-sm bg-gray-100 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-600 flex items-center justify-between">
                <span>{{ $product->updated_at ?? '—' }}</span>
                <span class="icon-calendar w-4 h-4 text-gray-400"></span>
            </div>
        </div>

        <!-- Created At -->
        <div class="mb-4">
            <label class="flex gap-1 items-center mb-1.5 text-xs text-gray-800 dark:text-white font-medium">
                @lang('admin::app.catalog.products.edit.product-info.created-at')
            </label>
            <div class="w-full py-2.5 px-3 border rounded-md text-sm bg-gray-100 text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-600 flex items-center justify-between">
                <span>{{ $product->created_at ?? '—' }}</span>
                <span class="icon-calendar w-4 h-4 text-gray-400"></span>
            </div>
        </div>
    </div>
</div>
<!-- End Panel -->
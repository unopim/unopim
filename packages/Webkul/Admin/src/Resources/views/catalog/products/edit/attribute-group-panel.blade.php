@php
    $customAttributes = $customAttributes instanceof \Illuminate\Support\Collection
        ? $customAttributes
        : $customAttributes->get();

    if (! empty($variantHiddenCodes)) {
        $customAttributes = $customAttributes->reject(fn ($attribute) => in_array($attribute->code, $variantHiddenCodes))->values();
    }

    $customAttributes->loadMissing('translations');

    // Only checkbox renders every option; select/multiselect resolve selected labels on demand.
    $customAttributes->where('type', 'checkbox')->loadMissing('options.translations');

    $groupLabel = $group->name;
    $groupLabel = empty($groupLabel) ? "[{$group->code}]" : $groupLabel;
@endphp

{!! view_render_event('unopim.admin.catalog.product.edit.form.column_before', ['product' => $product]) !!}

@if (count($customAttributes))
    <div
        class="flex flex-col gap-2"
        data-attribute-group="{{ $group->code }}"
        data-attribute-group-id="{{ $group->id }}"
    >

        {!! view_render_event('unopim.admin.catalog.product.edit.form.' . $group->code . '.before', ['product' => $product]) !!}

        <x-admin::accordion
            class="relative"
            :persist-key="'product-attribute-group.' . $group->code"
        >
            <x-slot:header class="!p-4">
                <p class="text-base text-gray-800 dark:text-white font-semibold">
                    {{ $groupLabel }}
                </p>
            </x-slot>

            <x-slot:content>
                <x-admin::products.dynamic-attribute-fields
                    :fields="$customAttributes"
                    :fieldValues="$product->values"
                    :currentLocaleCode="$currentLocale->code"
                    :currentChannelCode="$currentChannel->code"
                    :channelCurrencies="$currentChannel->currencies"
                    :variantFields="$product?->parent ? $product->parent->super_attributes->pluck('code')->toArray() : []"
                    :completeness-attributes="$requiredAttributes"
                    :locked-fields="($variantFieldLocks['locks'] ?? [])"
                    fieldsWrapper="values"
                >
                </x-admin::products.dynamic-attribute-fields>
            </x-slot>
        </x-admin::accordion>

        {!! view_render_event('unopim.admin.catalog.product.edit.form.' . $group->code . '.after', ['product' => $product]) !!}
    </div>
@endif

{!! view_render_event('unopim.admin.catalog.product.edit.form.column_after', ['product' => $product]) !!}

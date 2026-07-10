@props([
    'fields'            => [],
    'typeCode'          => '',
    'currentLocaleCode' => core()->getRequestedLocaleCode(),
])

{{--
    Renders the custom-field inputs for ONE association type's active
    `fields` (already resolved/translated by
    `ProductController::getAssociationTypeFieldForView()`), used by
    `catalog/products/edit/links.blade.php`.

    Field DEFINITIONS are static (known once per page load — you cannot add
    a new custom field from the product edit page), so this partial is
    emitted exactly ONCE per (association type, field) pair. Only the LIST
    OF LINKS (products) for a type is dynamic (added/removed at runtime via
    the product-search drawer), so the caller must place this component
    INSIDE its own `v-for="(link, index) in ..."` block — every `::name` /
    `::value` binding below intentionally references the fixed Vue-scope
    identifiers `link` and `index` supplied by that loop, plus the
    `assocField*` helper methods defined once on the enclosing
    `v-product-links` Vue component. Vue then replays this markup once per
    link, so no field markup is ever duplicated per link.

    Mirrors `components/categories/dynamic-fields.blade.php`'s per-type
    switch, adapted so values are read reactively from a link's
    `additional_data` (via the `assocField*` helpers) instead of a single
    Blade-resolved value.
--}}

@foreach ($fields as $field)
    @php
        $isLocalizable = (bool) ($field['value_per_locale'] ?? false);

        $fieldJson = json_encode($field);

        $nameExpr = "assocFieldName('{$typeCode}', index, {$fieldJson})";

        $controlType = in_array($field['type'], ['image', 'file']) ? 'text' : $field['type'];
    @endphp

    {!! view_render_event('unopim.admin.catalog.product.edit.form.links.field.before', ['field' => $field]) !!}

    <x-admin::form.control-group>
        <div class="inline-flex justify-between w-full">
            <x-admin::form.control-group.label ::for="{{ $nameExpr }}">
                {{ $field['label'] }}

                @if (! empty($field['is_required']))
                    <span class="required"></span>
                @endif
            </x-admin::form.control-group.label>

            @if ($isLocalizable)
                <div class="self-end mb-2 text-xs flex gap-1">
                    <span class="icon-language uppercase box-shadow p-1 rounded-full bg-gray-100 border border-gray-200 rounded text-gray-600 dark:!text-gray-600">
                        {{ $currentLocaleCode }}
                    </span>
                </div>
            @endif
        </div>

        @switch ($field['type'])
            @case ('checkbox')
                @foreach ($field['options'] ?? [] as $option)
                    <div class="flex py-2 items-center gap-2">
                        <x-admin::form.control-group.control
                            type="checkbox"
                            ::id="{{ $nameExpr }} + '_{{ $option['code'] }}_' + index"
                            ::name="({{ $nameExpr }}) + '[]'"
                            :value="$option['code']"
                            ::rules="{{ $field['rules'] }}"
                            :label="$field['label']"
                            ::for="{{ $nameExpr }} + '_{{ $option['code'] }}_' + index"
                            ::checked="assocFieldChecked(link, {{ $fieldJson }}, '{{ $option['code'] }}')"
                        />

                        <label
                            class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer select-none"
                            :for="{{ $nameExpr }} + '_{{ $option['code'] }}_' + index"
                        >
                            {{ $option['label'] }}
                        </label>
                    </div>
                @endforeach

                @break

            @case ('boolean')
                <input type="hidden" :name="{{ $nameExpr }}" value="false" />

                <x-admin::form.control-group.control
                    type="switch"
                    ::id="{{ $nameExpr }}"
                    ::name="{{ $nameExpr }}"
                    :label="$field['label']"
                    ::checked="assocFieldBoolean(link, {{ $fieldJson }})"
                    value="true"
                />

                @break

            @case ('select')
                <x-admin::form.control-group.control
                    type="select"
                    ::id="{{ $nameExpr }}"
                    ::name="{{ $nameExpr }}"
                    ::rules="{{ $field['rules'] }}"
                    :label="$field['label']"
                    ::value="JSON.stringify(assocFieldOption(link, {{ $fieldJson }}))"
                    :options="json_encode($field['options'] ?? [])"
                    track-by="code"
                    label-by="label"
                />

                @break

            @case ('multiselect')
                <x-admin::form.control-group.control
                    type="multiselect"
                    ::id="{{ $nameExpr }}"
                    ::name="{{ $nameExpr }}"
                    ::rules="{{ $field['rules'] }}"
                    :label="$field['label']"
                    ::value="JSON.stringify(assocFieldOptions(link, {{ $fieldJson }}))"
                    :options="json_encode($field['options'] ?? [])"
                    track-by="code"
                    label-by="label"
                />

                @break

            @default
                <x-admin::form.control-group.control
                    :type="$controlType"
                    ::id="{{ $nameExpr }}"
                    ::name="{{ $nameExpr }}"
                    ::rules="{{ $field['rules'] }}"
                    :label="$field['label']"
                    ::value="assocFieldValue(link, {{ $fieldJson }})"
                />
        @endswitch

        <v-error-message :name="{{ $nameExpr }}" v-slot="{ message }">
            <p
                class="mt-1 text-red-600 text-xs italic"
                v-text="message"
            >
            </p>
        </v-error-message>
    </x-admin::form.control-group>

    {!! view_render_event('unopim.admin.catalog.product.edit.form.links.field.after', ['field' => $field]) !!}
@endforeach

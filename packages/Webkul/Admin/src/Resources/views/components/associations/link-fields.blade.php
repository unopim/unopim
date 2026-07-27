{{--
    Renders the custom-field inputs for ONE association link, entirely at Vue
    runtime so a type loaded on page-load and a type attached later via the
    async picker render identically.

    MUST be placed inside the caller's `v-for="(link, index) in type.links"`
    block: every binding references the fixed Vue-scope identifiers `type`,
    `link` and `index`, plus the `assocField*` / `toggleAssocCheckboxOption`
    helpers and `currentLocaleCode` defined once on the enclosing
    `v-product-links` component. Field type drives which control renders via
    `v-if` branches (the control's `type` must be static at Blade compile time,
    so it cannot be a single dynamic binding).
--}}
<template
    v-for="field in type.fields"
    :key="'assoc-field-' + type.code + '-' + field.code + '-' + index"
>
    <x-admin::form.control-group>
        <div class="inline-flex justify-between w-full">
            <x-admin::form.control-group.label ::for="assocFieldName(type.code, index, field)">
                @{{ field.label }}

                <span v-if="field.is_required" class="required"></span>
            </x-admin::form.control-group.label>

            <div
                v-if="field.value_per_locale"
                class="self-end mb-2 text-xs flex gap-1"
            >
                <span
                    class="icon-language uppercase box-shadow p-1 rounded-full bg-gray-100 border border-gray-200 rounded text-gray-600 dark:!text-gray-600"
                    v-text="currentLocaleCode"
                >
                </span>
            </div>
        </div>

        <template v-if="field.type === 'checkbox'">
            {{--
                UI-only checkboxes: their `:name` is deliberately NOT the real
                `associations[...]` field path (and carries no `[]` suffix), so
                native FormData submission never collapses N checked boxes into a
                PHP array that `AssociationValidator`'s `string` rule would
                reject. The single hidden input below carries the real field
                name; `toggleAssocCheckboxOption()` keeps its comma-joined value
                in sync.
            --}}
            <div
                class="flex py-2 items-center gap-2"
                v-for="option in field.options"
                :key="'assoc-checkbox-' + field.code + '-' + option.code + '-' + index"
            >
                <x-admin::form.control-group.control
                    type="checkbox"
                    ::id="assocFieldName(type.code, index, field) + '_' + option.code + '_' + index"
                    ::name="'_assoc_checkbox_ui_' + index + '_' + option.code"
                    ::value="option.code"
                    ::for="assocFieldName(type.code, index, field) + '_' + option.code + '_' + index"
                    ::checked="assocFieldChecked(link, field, option.code)"
                    @change="toggleAssocCheckboxOption(link, field, option.code, $event.target.checked)"
                />

                <label
                    class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer select-none"
                    :for="assocFieldName(type.code, index, field) + '_' + option.code + '_' + index"
                    v-text="option.label"
                >
                </label>
            </div>

            <x-admin::form.control-group.control
                type="hidden"
                ::name="assocFieldName(type.code, index, field)"
                ::rules="field.rules"
                ::value="assocFieldValue(link, field)"
            />
        </template>

        <template v-else-if="field.type === 'boolean'">
            <input
                type="hidden"
                :name="assocFieldName(type.code, index, field)"
                value="false"
            />

            <x-admin::form.control-group.control
                type="switch"
                ::id="assocFieldName(type.code, index, field)"
                ::name="assocFieldName(type.code, index, field)"
                ::checked="assocFieldBoolean(link, field)"
                value="true"
            />
        </template>

        <template v-else-if="field.type === 'select'">
            <x-admin::form.control-group.control
                type="select"
                ::id="assocFieldName(type.code, index, field)"
                ::name="assocFieldName(type.code, index, field)"
                ::rules="field.rules"
                ::value="JSON.stringify(assocFieldOption(link, field))"
                ::options="field.options"
                track-by="code"
                label-by="label"
            />
        </template>

        <template v-else-if="field.type === 'multiselect'">
            <x-admin::form.control-group.control
                type="multiselect"
                ::id="assocFieldName(type.code, index, field)"
                ::name="assocFieldName(type.code, index, field)"
                ::rules="field.rules"
                ::value="JSON.stringify(assocFieldOptions(link, field))"
                ::options="field.options"
                track-by="code"
                label-by="label"
            />
        </template>

        <template v-else-if="field.type === 'textarea'">
            <x-admin::form.control-group.control
                type="textarea"
                ::id="assocFieldName(type.code, index, field)"
                ::name="assocFieldName(type.code, index, field)"
                ::rules="field.rules"
                ::value="assocFieldValue(link, field)"
            />
        </template>

        <template v-else-if="field.type === 'date'">
            <x-admin::form.control-group.control
                type="date"
                ::id="assocFieldName(type.code, index, field)"
                ::name="assocFieldName(type.code, index, field)"
                ::rules="field.rules"
                ::value="assocFieldValue(link, field)"
            />
        </template>

        <template v-else-if="field.type === 'datetime'">
            <x-admin::form.control-group.control
                type="datetime"
                ::id="assocFieldName(type.code, index, field)"
                ::name="assocFieldName(type.code, index, field)"
                ::rules="field.rules"
                ::value="assocFieldValue(link, field)"
            />
        </template>

        <template v-else>
            <x-admin::form.control-group.control
                type="text"
                ::id="assocFieldName(type.code, index, field)"
                ::name="assocFieldName(type.code, index, field)"
                ::rules="field.rules"
                ::value="assocFieldValue(link, field)"
            />
        </template>

        <v-error-message
            :name="assocFieldName(type.code, index, field)"
            v-slot="{ message }"
        >
            <p
                class="mt-1 text-red-600 text-xs italic"
                v-text="message"
            >
            </p>
        </v-error-message>
    </x-admin::form.control-group>
</template>

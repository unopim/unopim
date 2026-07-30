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
    v-for="assocField in (type.fields || [])"
    :key="'assoc-field-' + type.code + '-' + assocField.code + '-' + index"
>
    <x-admin::form.control-group
        class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-0"
        style="flex: 1 1 300px; min-width: 260px; max-width: 420px"
    >
        <div
            class="inline-flex justify-between items-center gap-1 shrink-0"
            style="width: 145px"
        >
            <x-admin::form.control-group.label
                class="mb-0 truncate"
                ::for="assocFieldName(type.code, index, assocField)"
                ::title="assocField.label"
            >
                @{{ assocField.label }}

                <span v-if="assocField.is_required" class="required"></span>
            </x-admin::form.control-group.label>

            <div
                v-if="assocField.value_per_locale"
                class="text-xs flex gap-1"
            >
                <span
                    class="icon-language uppercase box-shadow p-1 rounded-full bg-gray-100 border border-gray-200 rounded text-gray-600 dark:!text-gray-600"
                    v-text="currentLocaleCode"
                >
                </span>
            </div>
        </div>

        <div class="flex-1 min-w-0">

        <template v-if="assocField.type === 'checkbox'">
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
                v-for="option in assocField.options"
                :key="'assoc-checkbox-' + assocField.code + '-' + option.code + '-' + index"
            >
                <x-admin::form.control-group.control
                    type="checkbox"
                    ::id="assocFieldName(type.code, index, assocField) + '_' + option.code + '_' + index"
                    ::name="'_assoc_checkbox_ui_' + index + '_' + option.code"
                    ::value="option.code"
                    ::for="assocFieldName(type.code, index, assocField) + '_' + option.code + '_' + index"
                    ::checked="assocFieldChecked(link, assocField, option.code)"
                    @change="toggleAssocCheckboxOption(link, assocField, option.code, $event.target.checked)"
                />

                <label
                    class="text-xs text-gray-600 dark:text-gray-300 font-medium cursor-pointer select-none"
                    :for="assocFieldName(type.code, index, assocField) + '_' + option.code + '_' + index"
                    v-text="option.label"
                >
                </label>
            </div>

            <x-admin::form.control-group.control
                type="hidden"
                ::name="assocFieldName(type.code, index, assocField)"
                ::rules="assocField.rules"
                ::label="assocField.label"
                ::value="assocFieldValue(link, assocField)"
            />
        </template>

        <template v-else-if="assocField.type === 'boolean'">
            <input
                type="hidden"
                :name="assocFieldName(type.code, index, assocField)"
                value="false"
            />

            <x-admin::form.control-group.control
                type="switch"
                ::id="assocFieldName(type.code, index, assocField)"
                ::name="assocFieldName(type.code, index, assocField)"
                ::for="assocFieldName(type.code, index, assocField)"
                ::label="assocField.label"
                ::checked="assocFieldBoolean(link, assocField)"
                value="true"
            />
        </template>

        <template v-else-if="assocField.type === 'select'">
            <x-admin::form.control-group.control
                type="select"
                ::id="assocFieldName(type.code, index, assocField)"
                ::name="assocFieldName(type.code, index, assocField)"
                ::rules="assocField.rules"
                ::label="assocField.label"
                ::value="JSON.stringify(assocFieldOption(link, assocField))"
                ::options="assocField.options"
                track-by="code"
                label-by="label"
            />
        </template>

        <template v-else-if="assocField.type === 'multiselect'">
            <x-admin::form.control-group.control
                type="multiselect"
                ::id="assocFieldName(type.code, index, assocField)"
                ::name="assocFieldName(type.code, index, assocField)"
                ::rules="assocField.rules"
                ::label="assocField.label"
                ::value="JSON.stringify(assocFieldOptions(link, assocField))"
                ::options="assocField.options"
                track-by="code"
                label-by="label"
            />
        </template>

        <template v-else-if="assocField.type === 'textarea'">
            <x-admin::form.control-group.control
                type="textarea"
                ::id="assocFieldName(type.code, index, assocField)"
                ::name="assocFieldName(type.code, index, assocField)"
                ::rules="assocField.rules"
                ::label="assocField.label"
                ::value="assocFieldValue(link, assocField)"
            />
        </template>

        <template v-else-if="assocField.type === 'date'">
            <x-admin::form.control-group.control
                type="date"
                ::id="assocFieldName(type.code, index, assocField)"
                ::name="assocFieldName(type.code, index, assocField)"
                ::rules="assocField.rules"
                ::label="assocField.label"
                ::value="assocFieldValue(link, assocField)"
            />
        </template>

        <template v-else-if="assocField.type === 'datetime'">
            <x-admin::form.control-group.control
                type="datetime"
                ::id="assocFieldName(type.code, index, assocField)"
                ::name="assocFieldName(type.code, index, assocField)"
                ::rules="assocField.rules"
                ::label="assocField.label"
                ::value="assocFieldValue(link, assocField)"
            />
        </template>

        <template v-else>
            <x-admin::form.control-group.control
                type="text"
                ::id="assocFieldName(type.code, index, assocField)"
                ::name="assocFieldName(type.code, index, assocField)"
                ::rules="assocField.rules"
                ::label="assocField.label"
                ::value="assocFieldValue(link, assocField)"
            />
        </template>

        <v-error-message
            :name="assocFieldName(type.code, index, assocField)"
            v-slot="{ message }"
        >
            <p
                class="mt-1 text-red-600 text-xs italic"
                v-text="message"
            >
            </p>
        </v-error-message>

        </div>
    </x-admin::form.control-group>
</template>

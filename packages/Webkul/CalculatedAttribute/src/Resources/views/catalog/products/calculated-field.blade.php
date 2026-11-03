@php
    /**
     * Rendered in place of an input for a `calculated` attribute. The value was
     * already computed and merged into the form's field values before the panel
     * rendered.
     *
     * The control is named outside the form's `values` wrapper on purpose: a
     * read-only input is still submitted by the browser, and a derived value
     * that reached `values` would be written to the database as though someone
     * had typed it. The id still matches the label the product form rendered.
     */
    $displayValue = is_array($value ?? null)
        ? implode(', ', array_filter($value, static fn ($item): bool => $item !== null && $item !== ''))
        : (string) ($value ?? '');
@endphp

<div class="flex flex-col gap-1">
    <x-admin::form.control-group.control
        type="text"
        :id="form_control_id($fieldName)"
        :name="'calculated[' . $field->code . ']'"
        readonly
        tabindex="-1"
        value="{{ $displayValue }}"
        :label="trans('calculated_attribute::app.formula.read-only')"
        class="bg-gray-50 dark:bg-cherry-800 cursor-not-allowed"
    />

    <span class="text-xs text-gray-500 dark:text-gray-400">
        @lang('calculated_attribute::app.formula.read-only-hint')
    </span>
</div>

@php
    /**
     * Rendered in place of an input for a `calculated` attribute. The value was
     * already computed and merged into the form's field values by
     * admin::catalog.products.edit.attribute-group-panel.
     *
     * There is deliberately no `name` attribute and no hidden companion field:
     * a derived value has nothing to submit, and emitting one would invite the
     * product controller to persist it.
     */
    $displayValue = is_array($value ?? null)
        ? implode(', ', array_filter($value, static fn ($item): bool => $item !== null && $item !== ''))
        : (string) ($value ?? '');
@endphp

<div class="flex flex-col gap-1">
    <input
        id="{{ form_control_id($fieldName) }}"
        type="text"
        readonly
        tabindex="-1"
        value="{{ $displayValue }}"
        class="flex w-full min-h-[39px] py-2 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-cherry-800 dark:border-gray-800 cursor-not-allowed"
    >

    <span class="text-xs text-gray-500 dark:text-gray-400">
        @lang('calculated_attribute::app.formula.read-only-hint')
    </span>
</div>

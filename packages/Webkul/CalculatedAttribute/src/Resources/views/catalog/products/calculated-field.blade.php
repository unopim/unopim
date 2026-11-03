@php
    $displayValue = is_array($value ?? null)
        ? implode(', ', array_filter($value, static fn (mixed $item): bool => $item !== null && $item !== ''))
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

@php
    /**
     * Shared by the create and the edit form. `$attribute` only exists on edit,
     * so every read of it is guarded.
     */
    $calculatedAttribute = $attribute ?? null;

    $formulaValue = old('formula', $calculatedAttribute->formula ?? '');

    $functionNames = app(\Webkul\CalculatedAttribute\Expression\FormulaFunctions::class)->names();

    sort($functionNames);

    $systemVariables = \Webkul\CalculatedAttribute\Services\FormulaValidator::SYSTEM_VARIABLES;
@endphp

{{--
    v-show rather than v-if: the textarea stays in the DOM so switching the type
    back and forth does not discard what was typed. A formula submitted for a
    non-calculated type is dropped server-side by AttributeController.
--}}
<div
    class="mt-2 w-full bg-white dark:bg-cherry-900 box-shadow rounded"
    v-show="selectedAttributeType == 'calculated'"
>
    <div class="flex justify-between items-center p-1.5">
        <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
            @lang('calculated_attribute::app.formula.title')
        </p>
    </div>

    <div class="px-4 pb-4">
        <x-admin::form.control-group>
            <x-admin::form.control-group.label for="formula" class="required">
                @lang('calculated_attribute::app.formula.label')
            </x-admin::form.control-group.label>

            <textarea
                id="formula"
                name="formula"
                rows="3"
                spellcheck="false"
                autocapitalize="off"
                autocomplete="off"
                class="flex w-full py-2 px-3 border rounded-md text-sm font-mono text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 dark:focus:border-gray-400 focus:border-gray-400 dark:bg-cherry-800 dark:border-gray-800"
                placeholder="{{ trans('calculated_attribute::app.formula.placeholder') }}"
            >{{ $formulaValue }}</textarea>

            <x-admin::form.control-group.error control-name="formula" />

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                @lang('calculated_attribute::app.formula.info')
            </p>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                @lang('calculated_attribute::app.formula.reference-hint')
            </p>
        </x-admin::form.control-group>

        <details class="mt-3">
            <summary class="cursor-pointer text-xs font-semibold text-gray-600 dark:text-gray-300">
                @lang('calculated_attribute::app.formula.available')
            </summary>

            <div class="mt-2 flex flex-wrap gap-1">
                @foreach ($functionNames as $functionName)
                    <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-cherry-800 text-xs text-gray-700 dark:text-gray-300">{{ $functionName }}</code>
                @endforeach
            </div>

            <p class="mt-3 text-xs font-semibold text-gray-600 dark:text-gray-300">
                @lang('calculated_attribute::app.formula.variables.title')
            </p>

            <ul class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                @foreach ($systemVariables as $variable)
                    <li>
                        <code>{{ $variable }}</code>
                        — @lang('calculated_attribute::app.formula.variables.' . $variable)
                    </li>
                @endforeach
            </ul>

            <p class="mt-3 text-xs font-semibold text-gray-600 dark:text-gray-300">
                @lang('calculated_attribute::app.formula.examples')
            </p>

            <ul class="mt-1 text-xs text-gray-500 dark:text-gray-400 flex flex-col gap-1">
                <li>
                    <code>concat('-', manufacturer, manufacturer_part_number)</code>
                    <br>@lang('calculated_attribute::app.formula.example-concat')
                </li>
                <li>
                    <code>category_label_path('#')</code>
                    <br>@lang('calculated_attribute::app.formula.example-category')
                </li>
                <li>
                    <code>coalesce(short_description, name)</code>
                    <br>@lang('calculated_attribute::app.formula.example-fallback')
                </li>
            </ul>
        </details>
    </div>
</div>

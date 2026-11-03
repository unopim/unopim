@php
    /**
     * Shared by the create and the edit form. `$attribute` only exists on edit,
     * so every read of it is guarded.
     *
     * The panel uses v-show rather than v-if so the control stays in the DOM:
     * switching the type back and forth must not discard what was typed. A
     * formula submitted for a non-calculated type is discarded when it is
     * saved.
     *
     * The function list and the variable list are supplied by a view composer,
     * so nothing here has to reach into the container.
     */
    $calculatedAttribute = $attribute ?? null;

    $formulaValue = old('formula', $calculatedAttribute->formula ?? '');
@endphp

<div
    class="mt-2 w-full bg-white dark:bg-cherry-900 box-shadow rounded"
    v-show="selectedAttributeType == '{{ $calculatedType }}'"
>
    <div class="flex justify-between items-center p-1.5">
        <p class="p-2.5 text-gray-800 dark:text-white text-base font-semibold">
            @lang('calculated_attribute::app.formula.title')
        </p>
    </div>

    <div class="px-4 pb-4">
        <x-admin::form.control-group>
            <x-admin::form.control-group.label class="required">
                @lang('calculated_attribute::app.formula.label')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="textarea"
                id="formula"
                name="formula"
                rows="3"
                spellcheck="false"
                autocapitalize="off"
                autocomplete="off"
                class="font-mono"
                value="{{ $formulaValue }}"
                :label="trans('calculated_attribute::app.formula.label')"
                :placeholder="trans('calculated_attribute::app.formula.placeholder')"
            />

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
                @foreach ($formulaFunctions as $functionName)
                    <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-cherry-800 text-xs text-gray-700 dark:text-gray-300">{{ $functionName }}</code>
                @endforeach
            </div>

            <p class="mt-3 text-xs font-semibold text-gray-600 dark:text-gray-300">
                @lang('calculated_attribute::app.formula.variables.title')
            </p>

            <ul class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                @foreach ($formulaVariables as $variable)
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

@php
    $attributeId = $field->attribute->id ?? $field->id;

    $formatValue = function ($val) {
        if ($val === '' || $val === null) {
            return '';
        }

        $num = (float) $val;

        if (floor($num) == $num) {
            return (int) $num;
        }

        $formatted = rtrim(
            rtrim(number_format($num, 4, '.', ''), '0'),
            '.'
        );

        if (strpos($formatted, '.') !== false) {
            $parts = explode('.', $formatted);

            if (strlen($parts[1]) == 1) {
                $formatted .= '0';
            }
        }

        return $formatted;
    };

    if (isset($value)) {
        $currentValue = $formatValue($value['amount'] ?? '');
        $currentUnit  = $value['unit'] ?? '__auto__';
    } else {
        $currentValue = $formatValue($value['value'] ?? '');

        $currentUnit = empty($currentValue)
            ? '__auto__'
            : ($value['unit'] ?? '__auto__');
    }
@endphp

<div class="grid gap-4 [grid-template-columns:repeat(auto-fit,_minmax(200px,_1fr))]">
    <!-- Value Field -->
    <div class="grid w-full">
        <x-admin::form.control-group.control
            type="text"
            name="{{ $fieldName }}[value]"
            :label="$field->name ?: $field->code"
            ::rules="{{ $field->getValidationsField() }}"
            :value="$currentValue"
            placeholder="Enter value"
            oninput="
                this.value = this.value
                    .replace(/[^0-9.]/g, '')
                    .replace(/(\..*?)\..*/g, '$1');
            "
        />

        <x-admin::form.control-group.error :control-name="$fieldName.'[value]'" />
    </div>

    <!-- Unit Field -->
    <div class="grid w-full">
        <x-admin::form.control-group.control
            type="select"
            name="{{ $fieldName }}[unit]"
            async="true"
            track-by="id"
            label-by="label"
            :value="$currentUnit"
            :list-route="route('admin.measurement.attribute.units', [
                'attribute_id' => $attributeId,
                'queryParams' => [
                    'identifiers' => [
                        'columnName' => 'id',
                        'value'      => $currentUnit,
                    ],
                ],
            ])"
        />
    </div>
</div>
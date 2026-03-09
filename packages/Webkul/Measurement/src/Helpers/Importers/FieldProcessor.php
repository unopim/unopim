<?php

namespace Webkul\Measurement\Helpers\Importers;

use Webkul\DataTransfer\Helpers\Importers\FieldProcessor as CoreFieldProcessor;

class FieldProcessor extends CoreFieldProcessor
{
    public function handleField($field, mixed $value, string $path)
    {

        if ($field->type === 'measurement' && ! empty($value)) {

            if (is_string($value)) {
                $value = str_replace('|', ',', $value);
                [$unit, $val] = array_map('trim', explode(',', $value, 2));

                return [
                    'unit'  => strtoupper($unit),
                    'value' => $val,
                ];
            }

            return $value;
        }

        return parent::handleField($field, $value, $path);
    }
}

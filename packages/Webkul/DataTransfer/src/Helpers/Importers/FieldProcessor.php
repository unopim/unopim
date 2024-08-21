<?php

namespace Webkul\DataTransfer\Helpers\Importers;

use Illuminate\Support\Facades\Storage as StorageFacade;

class FieldProcessor
{
    public function handleField($field, mixed $value, string $path)
    {
        if (empty($value)) {
            return;
        }

        switch ($field->type) {
            case 'image':
            case 'file':
                $value = $this->handleMediaField($path.$value);

                break;
            case 'textarea':
                if ($field->enable_wysiwyg) {
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                }

                break;
            default:
                break;
        }

        return $value;
    }

    protected function handleMediaField(mixed $value)
    {
        if (! StorageFacade::disk('local')->has('public/'.$value)) {
            return;
        }

        return $value;
    }
}

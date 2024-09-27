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
            case 'gallery':
                $value = $this->handleMediaField($value, $path);

                break;
            case 'image':
            case 'file':
                $value = $this->handleMediaField($value, $path);
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

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

    protected function handleMediaField(mixed $value, $imgpath)
    {
        $paths = is_array($value) ? $value : [$value];
        $validPaths = [];

        foreach ($paths as $path) {
            $trimmedPath = trim($path);

            if (StorageFacade::disk('local')->has('public/'.$imgpath.$trimmedPath)) {
                $validPaths[] = $imgpath.$trimmedPath;
            }
        }

        return count($validPaths) ? $validPaths : null;
    }
}

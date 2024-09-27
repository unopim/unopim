<?php

namespace Webkul\DataTransfer\Helpers\Importers;

use Illuminate\Support\Facades\Storage as StorageFacade;

class FieldProcessor
{
    /**
     * Processes a field value based on its type.
     *
     * @param  object  $field  The field object.
     * @param  mixed  $value  The value of the field.
     * @param  string  $path  The path to the media files.
     * @return mixed The processed value of the field.
     */
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

    /**
     * Processes media fields value.
     *
     * @param  mixed  $value  The value of the media field.
     * @param  string  $imgpath  The path to the media files.
     * @return array|null valid paths of the media files, or null if none are found.
     */
    protected function handleMediaField(mixed $value, $imgpath): ?array
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

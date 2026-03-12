<?php

namespace Webkul\DataTransfer\Helpers\Importers;

use Illuminate\Support\Facades\Storage as StorageFacade;
use Webkul\Core\Traits\HtmlPurifier;

class FieldProcessor
{
    use HtmlPurifier;

    /**
     * Static cache for filesystem existence checks.
     * Shared across all rows in the same worker process — avoids redundant
     * Storage::disk('local')->has() syscalls for the same image paths.
     */
    protected static array $pathExistsCache = [];

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
                    $value = $this->purifyText($value);
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
    public function handleMediaField(mixed $value, string $imgpath): ?array
    {
        $paths = is_array($value) ? $value : [$value];
        $validPaths = [];

        $baseDir = rtrim($imgpath, '/');

        foreach ($paths as $path) {
            $trimmedPath = ltrim(trim($path), '/');

            $fullPath = $baseDir.'/'.$trimmedPath;
            $storagePath = 'public/'.$fullPath;

            if (! array_key_exists($storagePath, self::$pathExistsCache)) {
                self::$pathExistsCache[$storagePath] = StorageFacade::disk('local')->exists($storagePath);
            }

            if (self::$pathExistsCache[$storagePath]) {
                $validPaths[] = $fullPath;
            }
        }

        return count($validPaths) ? $validPaths : null;
    }
}

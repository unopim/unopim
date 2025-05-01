<?php

namespace Webkul\DataTransfer\Helpers\Importers;

use HTMLPurifier;
use HTMLPurifier_Config;
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
                $value = $this->handleTextareaField($field, $value);

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
    protected function handleMediaField(mixed $value, string $imgpath): ?array
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

    /**
     * Processes textarea fields value.
     *
     * @param  object  $field  The field object.
     * @param  mixed  $value  The value of the field.
     * @return mixed The processed value of the field.
     */
    protected function handleTextareaField(object $field, mixed $value): mixed
    {
        if ($field->enable_wysiwyg) {
            $value = htmlspecialchars_decode($value, ENT_QUOTES);
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,b,a[href],i,em,strong,ul,ol,li,br,img[src|alt|width|height],h2,h3,h4,table,thead,tbody,tr,th,td');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
            $config->set('AutoFormat.AutoParagraph', true);
            $config->set('HTML.SafeIframe', true);
            $config->set('HTML.SafeObject', true);

            $purifier = new HTMLPurifier($config);
            $value = $purifier->purify($value);

        }

        return $value;
    }
}

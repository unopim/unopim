<?php

namespace Webkul\DataTransfer\Helpers\Importers;

use Illuminate\Support\Facades\Storage as StorageFacade;
use Webkul\Core\Traits\HtmlPurifier;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\Measurement\Helpers\MeasurementHelper;

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
     * Resolved once per instance rather than per field, since handleField runs
     * for every column of every imported row.
     */
    protected ?MeasurementHelper $measurementHelper = null;

    /**
     * Resolve the measurement helper lazily, so an installation without any
     * measurement attribute never builds it.
     */
    protected function measurementHelper(): MeasurementHelper
    {
        return $this->measurementHelper ??= resolve(MeasurementHelper::class);
    }

    /**
     * Processes a field value based on its type.
     *
     * @param  object  $field  The field object.
     * @param  mixed  $value  The value of the field.
     * @param  string  $path  The path to the media files.
     * @return mixed The processed value of the field.
     */
    public function handleField($field, mixed $value, ?string $path)
    {
        if ($field->type === 'measurement' && ! empty($value)) {
            return $this->handleMeasurementField($field, $value);
        }

        if (empty($value)) {
            return;
        }

        switch ($field->type) {
            case 'gallery':
                if ($path !== null) {
                    $value = $this->handleMediaField($value, $path);
                }

                break;
            case 'image':
            case 'file':
                if ($path !== null) {
                    $value = $this->handleMediaField($value, $path);
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
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
     * Build the stored measurement structure from an import cell.
     *
     * Accepts "<unit>,<amount>" / "<unit>|<amount>" strings and ['value' => …,
     * 'unit' => …] arrays; anything else is handed back untouched so the row
     * validator reports it rather than this method guessing.
     */
    protected function handleMeasurementField($field, mixed $value): mixed
    {
        $measurementValue = null;
        $measurementUnit = null;

        if (is_string($value)) {
            $value = str_replace('|', ',', $value);
            [$unit, $amount] = array_map(trim(...), explode(',', $value, 2));
            $measurementValue = $amount;
            $measurementUnit = $unit;
        } elseif (is_array($value) && array_key_exists('value', $value) && array_key_exists('unit', $value)) {
            $measurementValue = $value['value'];
            $measurementUnit = $value['unit'];
        }

        if ($measurementValue === null || $measurementUnit === null || $measurementUnit === '') {
            return $value;
        }

        $measurementHelper = $this->measurementHelper();

        return $measurementHelper->getMeasurementValueStructure(
            (float) EscapeFormulaOperators::unescapeValue($measurementValue),
            $measurementHelper->resolveUnitCode($measurementUnit, $field),
            $field
        );
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
            $trimmedPath = ltrim(trim((string) $path), '/');

            if ($trimmedPath === '') {
                continue;
            }

            $fullPath = $baseDir === '' ? $trimmedPath : $baseDir.'/'.$trimmedPath;
            $storagePath = 'public/'.$fullPath;

            if (! array_key_exists($storagePath, self::$pathExistsCache)) {
                self::$pathExistsCache[$storagePath] = StorageFacade::disk('local')->fileExists($storagePath);
            }

            if (self::$pathExistsCache[$storagePath]) {
                $validPaths[] = $fullPath;
            }
        }

        return count($validPaths) ? $validPaths : null;
    }
}

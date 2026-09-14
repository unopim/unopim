<?php

namespace Webkul\Core\Helpers;

use Symfony\Component\Mime\MimeTypes;

class MediaMimeTypes
{
    public static function normalizeExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'jpeg', 'jfif', 'jif' => 'jpg',
            'tiff'                => 'tif',
            default               => strtolower($extension),
        };
    }

    /**
     * @param  list<string>  $extensions
     * @return array<string, list<string>>
     */
    public function forExtensions(array $extensions): array
    {
        $result = [];

        foreach ($extensions as $extension) {
            $extension = strtolower(ltrim(trim($extension), '.'));

            if ($extension === '') {
                continue;
            }

            $result[$extension] = MimeTypes::getDefault()->getMimeTypes(self::normalizeExtension($extension));
        }

        return $result;
    }
}

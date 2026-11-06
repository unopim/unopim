<?php

namespace Webkul\DataTransfer\Helpers;

/**
 * The export filter fields the export form lays out in its own cards; any other field lands in the generic card.
 */
final class ExportFilterFields
{
    public const array KNOWN = [
        'channels',
        'locales',
        'currencies',
        'attributes',
        'attribute_families',
        'categories',
        'completeness',
        'time_condition',
        'time_value',
        'time_date',
        'time_date_end',
        'status',
        'sku',
        'custom_attributes',
        'file_format',
        'with_media',
        'with_associations',
        'header_row',
        'use_labels',
        'date_format',
        'file_path',
    ];

    /**
     * The known field names as the comma separated list the filter-fields component takes.
     */
    public static function knownList(): string
    {
        return implode(',', self::KNOWN);
    }
}

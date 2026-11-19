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
     * Whether an exporter opts in, with `filters.generic_card`, to show its other filter fields in the generic card.
     *
     * Connectors that place their own fields through view render events leave it unset, so no field renders twice.
     */
    public static function usesGenericCard(mixed $exporter): bool
    {
        return is_array($exporter) && ! empty($exporter['filters']['generic_card']);
    }

    /**
     * The entity types of the exporters that opt in to the generic card.
     *
     * @param  array<array-key, mixed>  $exporters
     * @return list<array-key>
     */
    public static function genericCardTypes(array $exporters): array
    {
        return array_keys(array_filter($exporters, self::usesGenericCard(...)));
    }

    /**
     * The known field names as the comma separated list the filter-fields component takes.
     */
    public static function knownList(): string
    {
        return implode(',', self::KNOWN);
    }
}

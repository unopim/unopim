<?php

namespace Webkul\Attribute\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class AttributeHistoryPresenter implements HistoryPresenterInterface
{
    protected static array $fieldLabelMap = [
        'type'              => 'admin::app.catalog.attributes.create.type',
        'validation'        => 'admin::app.catalog.attributes.create.input-validation',
        'regex_pattern'     => 'admin::app.catalog.attributes.create.regex',
        'swatch_type'       => 'admin::app.catalog.attributes.create.swatch',
        'is_required'       => 'admin::app.catalog.attributes.create.is-required',
        'is_unique'         => 'admin::app.catalog.attributes.create.is-unique',
        'enable_wysiwyg'    => 'admin::app.catalog.attributes.create.enable-wysiwyg',
        'value_per_locale'  => 'admin::app.catalog.attributes.create.value-per-locale',
        'value_per_channel' => 'admin::app.catalog.attributes.create.value-per-channel',
        'is_filterable'     => 'admin::app.catalog.attributes.create.is-filterable',
        'ai_translate'      => 'admin::app.catalog.attributes.create.ai-translate',
        'position'          => 'admin::app.catalog.attributes.create.position',
    ];

    protected static array $booleanFields = [
        'is_required',
        'is_unique',
        'enable_wysiwyg',
        'value_per_locale',
        'value_per_channel',
        'is_filterable',
        'ai_translate',
    ];

    /**
     * {@inheritdoc}
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        $label = isset(static::$fieldLabelMap[$fieldName])
            ? trans(static::$fieldLabelMap[$fieldName])
            : $fieldName;

        return [
            $fieldName => [
                'name' => $label,
                'old'  => static::formatValue($fieldName, $oldValues),
                'new'  => static::formatValue($fieldName, $newValues),
            ],
        ];
    }

    protected static function formatValue(string $fieldName, mixed $value): mixed
    {
        if (in_array($fieldName, static::$booleanFields)) {
            return $value
                ? trans('admin::app.catalog.attributes.create.yes')
                : trans('admin::app.catalog.attributes.create.no');
        }

        if ($fieldName === 'type') {
            $config = config("attribute_types.{$value}");

            return $config ? trans($config['name']) : $value;
        }

        return $value;
    }
}

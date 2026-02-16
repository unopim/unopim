<?php

namespace Webkul\ChannelConnector\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;

class ConnectorHistoryPresenter implements HistoryPresenterInterface
{
    /**
     * Credential-like keys that must never appear in history records.
     */
    protected static array $sensitiveKeys = [
        'credentials',
        'access_token',
        'refresh_token',
        'api_key',
        'api_secret',
        'client_secret',
        'webhook_secret',
        'token',
        'password',
    ];

    /**
     * {@inheritdoc}
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        return match ($fieldName) {
            'status'       => static::presentLabelChange($oldValues, $newValues, $fieldName, 'channel_connector::app.connectors.status.'),
            'channel_type' => static::presentLabelChange($oldValues, $newValues, $fieldName, 'channel_connector::app.connectors.channel-types.'),
            'settings'     => static::presentSettings($oldValues, $newValues, $fieldName),
            default        => static::presentSimple($oldValues, $newValues, $fieldName),
        };
    }

    /**
     * Present a field whose raw value should be translated to a human-readable label.
     */
    protected static function presentLabelChange(mixed $oldValue, mixed $newValue, string $fieldName, string $transPrefix): array
    {
        $old = ! empty($oldValue) ? trans($transPrefix.$oldValue) : '';
        $new = ! empty($newValue) ? trans($transPrefix.$newValue) : '';

        if ($old === $new) {
            return [];
        }

        return [
            $fieldName => [
                'name' => $fieldName,
                'old'  => $old,
                'new'  => $new,
            ],
        ];
    }

    /**
     * Present settings changes as a flat key-value diff, stripping sensitive keys.
     */
    protected static function presentSettings(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        $oldArray = static::decodeSettings($oldValues);
        $newArray = static::decodeSettings($newValues);

        $oldArray = static::sanitize($oldArray);
        $newArray = static::sanitize($newArray);

        if (empty($oldArray) && empty($newArray)) {
            return [];
        }

        $normalizedData = [];

        $removed = static::calculateDifference($oldArray, $newArray);
        $updated = static::calculateDifference($newArray, $oldArray);

        foreach ($removed as $key => $value) {
            $label = $fieldName.'.'.$key;

            $normalizedData[$label] = [
                'name' => $label,
                'old'  => is_array($value) ? json_encode($value) : (string) $value,
            ];
        }

        foreach ($updated as $key => $value) {
            $label = $fieldName.'.'.$key;

            if (! isset($normalizedData[$label])) {
                $normalizedData[$label] = ['name' => $label];
            }

            $normalizedData[$label]['new'] = is_array($value) ? json_encode($value) : (string) $value;
        }

        return $normalizedData;
    }

    /**
     * Present a simple scalar field change (name, code, etc.).
     */
    protected static function presentSimple(mixed $oldValue, mixed $newValue, string $fieldName): array
    {
        $old = is_array($oldValue) ? json_encode($oldValue) : (string) ($oldValue ?? '');
        $new = is_array($newValue) ? json_encode($newValue) : (string) ($newValue ?? '');

        if ($old === $new) {
            return [];
        }

        return [
            $fieldName => [
                'name' => $fieldName,
                'old'  => $old,
                'new'  => $new,
            ],
        ];
    }

    /**
     * Decode settings value from JSON string or return array as-is.
     */
    protected static function decodeSettings(mixed $value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }

        if (is_array($value)) {
            return $value;
        }

        return [];
    }

    /**
     * Remove any sensitive keys from the settings array.
     */
    protected static function sanitize(array $data): array
    {
        foreach (static::$sensitiveKeys as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    /**
     * Calculate difference between two arrays (keys in $current that differ from $comparing).
     */
    protected static function calculateDifference(array $current, array $comparing): array
    {
        $changes = [];

        foreach ($current as $key => $currentValue) {
            if (! isset($comparing[$key])) {
                $changes[$key] = $currentValue;

                continue;
            }

            if ($currentValue != $comparing[$key]) {
                $changes[$key] = $currentValue;
            }
        }

        return $changes;
    }
}

<?php

namespace Webkul\Admin\Fields;

class FieldConfig
{
    /**
     * @var array<string, array{key: string, sets: array<string, array<int, array<string, mixed>>>, types: string[]}>
     */
    protected array $cache = [];

    /**
     * Normalize an exporter/importer config into the field arrays the browser consumes.
     *
     * @param  array<string, mixed>|string  $config
     * @return array{key: string, sets: array<string, array<int, array<string, mixed>>>, types: string[]}
     */
    public function payload(array|string $config): array
    {
        $config = $this->toArray($config);

        $key = md5(app()->getLocale().serialize($config));

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $sets = [];
        $types = [];

        foreach ($config as $entity => $definition) {
            $filters = is_array($definition) ? ($definition['filters'] ?? null) : null;

            $fields = is_array($filters) ? ($filters['fields'] ?? null) : null;

            $sets[(string) $entity] = array_map(
                fn (mixed $field): array => $this->field(is_array($field) ? $field : []),
                array_values(is_array($fields) ? $fields : []),
            );

            foreach ($sets[(string) $entity] as $field) {
                $types[] = $this->str($field['type'] ?? null, 'text');
            }
        }

        return $this->cache[$key] = [
            'key'   => substr($key, 0, 12),
            'sets'  => $sets,
            'types' => array_values(array_unique($types)),
        ];
    }

    /**
     * @param  array<mixed>  $field
     * @return array<string, mixed>
     */
    public function field(array $field): array
    {
        $async = (bool) ($field['async'] ?? false);

        $route = is_string($field['list_route'] ?? null) && $field['list_route'] !== ''
            ? $field['list_route']
            : null;

        return [
            'name'         => $this->str($field['name'] ?? null),
            'type'         => $this->str($field['type'] ?? null, 'text'),
            'label'        => $this->trans($field['title'] ?? null),
            'info'         => $this->trans($field['info'] ?? null),
            'required'     => (bool) ($field['required'] ?? false),
            'validation'   => $field['validation'] ?? null,
            'default'      => $field['default'] ?? null,
            'placeholder'  => $field['placeholder'] ?? null,
            'options'      => $this->options($field['options'] ?? null),
            'async'        => $async,
            'list_route'   => $route === null ? null : ($async ? route($route) : $route),
            'track_by'     => $field['track_by'] ?? null,
            'label_by'     => $field['label_by'] ?? null,
            'query_params' => $field['query_params'] ?? null,
            'full_width'   => (bool) ($field['full_width'] ?? false),
            'visible_when' => $field['visible_when'] ?? null,
            'depends_on'   => $field['depends_on'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>|string  $config
     * @return array<string, mixed>
     */
    protected function toArray(array|string $config): array
    {
        if (is_string($config)) {
            $decoded = $config === '' ? null : json_decode($config, true);

            $config = is_array($decoded) ? $decoded : [];
        }

        /** @var array<string, mixed> $config */
        return $config;
    }

    protected function str(mixed $value, string $default = ''): string
    {
        return is_string($value) && $value !== '' ? $value : $default;
    }

    protected function trans(mixed $key): ?string
    {
        if (! is_string($key) || $key === '') {
            return null;
        }

        $translated = trans($key);

        return is_string($translated) ? $translated : $key;
    }

    /**
     * @return array<mixed>|null
     */
    protected function options(mixed $options): ?array
    {
        if (! is_array($options)) {
            return null;
        }

        return array_values(array_map(function (mixed $option): mixed {
            if (is_array($option) && isset($option['label'])) {
                $option['label'] = $this->trans($option['label']);
            }

            return $option;
        }, $options));
    }
}

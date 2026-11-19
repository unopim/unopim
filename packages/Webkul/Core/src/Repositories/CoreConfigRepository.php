<?php

namespace Webkul\Core\Repositories;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Contracts\CoreConfig;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Traits\CoreConfigField;

class CoreConfigRepository extends Repository
{
    use CoreConfigField;

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return CoreConfig::class;
    }

    /**
     * Create.
     */
    public function create(array $data): void
    {
        Event::dispatch('core.configuration.save.before');

        $channel = $locale = null;

        // Strip scope keys even when null, else a null channel/locale reaches array-typed recursiveArray() and fatals.
        if (array_key_exists('locale', $data) || array_key_exists('channel', $data)) {
            $locale = ($data['locale'] ?? null) ?: null;
            $channel = ($data['channel'] ?? null) ?: null;

            unset($data['locale'], $data['channel']);
        }

        foreach ($data as $method => $fieldData) {
            $recursiveData = $this->recursiveArray($fieldData, $method);

            foreach ($recursiveData as $fieldName => $value) {
                $field = core()->getConfigField($fieldName);

                // Fall back to default_value, else empty string so explicitly cleared fields are persisted.
                if (is_null($value)) {
                    $value = isset($field['default_value']) && $field['default_value'] !== '' ? $field['default_value'] : '';
                }

                if (($field['type'] ?? null) === 'password' && is_string($value) && preg_match('/^\*+$/', $value)) {
                    $original = core()->getConfigData($fieldName);

                    if (! is_null($original)) {
                        $value = $original;
                    }
                }

                if (is_array($value) && ! isset($value['delete'])) {
                    $value = implode(',', $value);
                }

                $coreConfigValue = $this->model->where('code', $fieldName)->get();

                if (request()->hasFile($fieldName)) {
                    $value = request()->file($fieldName)->store('configuration');
                }

                if (count($coreConfigValue) === 0) {
                    parent::create([
                        'code'         => $fieldName,
                        'value'        => $value,
                        'locale_code'  => $locale,
                        'channel_code' => $channel,
                    ]);
                } else {
                    foreach ($coreConfigValue as $coreConfig) {
                        if (request()->hasFile($fieldName)) {
                            Storage::delete($coreConfig['value']);
                        }

                        if (isset($value['delete'])) {
                            parent::delete($coreConfig['id']);
                        } else {
                            parent::update([
                                'code'         => $fieldName,
                                'value'        => $value,
                                'locale_code'  => $locale,
                                'channel_code' => $channel,
                            ], $coreConfig->id);
                        }
                    }
                }
            }
        }

        Event::dispatch('core.configuration.save.after');
    }

    /**
     * Search configuration.
     *
     * @param  array  $items
     * @param  string  $searchTerm
     */
    public function search($items, $searchTerm, $path = []): array
    {
        $results = [];

        foreach ($items as $configuration) {
            $title = trans($configuration['title'] ?? ($configuration['name'] ?? ''));

            if (
                stripos($title, $searchTerm) !== false
                && count($path)
            ) {
                $queryParam = isset($path[1]) ? $path[1]['key'] : $configuration['key'];

                $results[] = [
                    'title' => implode(' > ', [...Arr::pluck($path, 'title'), $title]),
                    'url'   => route('admin.configuration.edit', Str::replace('.', '/', $queryParam)),
                ];
            }

            if (
                ! empty($configuration['children'])
                || ! empty($configuration['fields'])
            ) {
                $children = empty($configuration['children'])
                    ? $configuration['fields']
                    : $configuration['children'];

                $tempPath = array_merge($path, [[
                    'key'   => $configuration['key'] ?? null,
                    'title' => trans($configuration['name'] ?? ''),
                ]]);

                $results = array_merge($results, $this->search($children, $searchTerm, $tempPath));
            }
        }

        return $results;
    }

    /**
     * Flatten submitted form data into config codes and their values.
     *
     * @return array
     */
    public function recursiveArray(array $formData, string $method)
    {
        $data = [];

        $this->collectFieldGroups($formData, $method, $data);

        $recursiveArrayData = [];

        foreach ($data as $key => $value) {
            $field = core()->getConfigField($key);

            if ($field) {
                $recursiveArrayData[$key] = $value;
            } else {
                foreach ($value as $key1 => $val) {
                    $recursiveArrayData[$key.'.'.$key1] = $val;
                }
            }
        }

        return $recursiveArrayData;
    }

    /**
     * Collect the innermost arrays of the form data, keyed by their dotted path.
     *
     * @param  array<string, mixed>  $data
     */
    protected function collectFieldGroups(array $formData, string $method, array &$data): void
    {
        foreach ($formData as $form => $formValue) {
            if (! is_array($formValue)) {
                continue;
            }

            $path = $method.'.'.$form;

            if ($this->countDim($formValue) > 1) {
                $this->collectFieldGroups($formValue, $path, $data);
            } else {
                $data[$path] = $formValue;
            }
        }
    }

    /**
     * Return dimension of the array.
     *
     * @param  array  $array
     * @return int
     */
    public function countDim($array): int|float
    {
        if (is_array(reset($array))) {
            return $this->countDim(reset($array)) + 1;
        }

        return 1;
    }
}

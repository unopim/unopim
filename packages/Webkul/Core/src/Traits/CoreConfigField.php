<?php

namespace Webkul\Core\Traits;

use Illuminate\Support\Str;

trait CoreConfigField
{
    /**
     * Laravel to Vee Validation mappings.
     *
     * @var array
     */
    protected $veeValidateMappings = [
        'min'=> 'min_value',
    ];

    /**
     * Get name field for forms in configuration page.
     *
     * @param  string  $key
     */
    public function getNameField($key): string
    {
        $nameField = '';

        foreach (explode('.', $key) as $key => $field) {
            $nameField .= $key === 0 ? $field : '['.$field.']';
        }

        return $nameField;
    }

    /**
     * Get validations for forms in configuration page.
     *
     * @return string
     */
    public function getValidations(array $field)
    {
        $field['validation'] ??= '';

        foreach ($this->veeValidateMappings as $laravelRule => $veeValidateRule) {
            $field['validation'] = str_replace($laravelRule, $veeValidateRule, $field['validation']);
        }

        return $field['validation'];
    }

    /**
     * Get value from repositories, if developer wants to do.
     *
     * @return mixed
     */
    public function getValueByRepository(array $field)
    {
        if (isset($field['repository'])) {
            [$class, $method] = Str::parseCallback($field['repository']);

            return resolve($class)->$method();
        }

        return null;
    }

    /**
     * Get dependent field or value based on arguments.
     *
     * @param  string  $fieldOrValue
     * @return string
     */
    public function getDependentFieldOrValue(array $field, $fieldOrValue = 'field')
    {
        $depends = explode(':', $field['depends']);

        return $fieldOrValue === 'field'
            ? current($depends) : end($depends);
    }

    /**
     * Get dependent field options.
     *
     * @param  array  $dependentValues
     */
    public function getDependentFieldOptions(array $field, $dependentValues): string|array
    {
        if (
            empty($field['options'])
            || ! $dependentValues
        ) {
            return '';
        }

        $options = [];

        foreach ($dependentValues as $key => $result) {
            $options[] = [
                'title' => $result,
                'value' => $key,
            ];
        }

        return $options;
    }

    /**
     * Get channel/locale indicator for form fields. So, that form fields can be detected,
     * whether it is channel based or locale based or both.
     *
     * @param  string  $channel
     * @param  string  $locale
     */
    public function getChannelLocaleInfo(array $field, $channel, $locale): string
    {
        $info = [];

        if (! empty($field['channel_based'])) {
            $info[] = $channel;
        }

        if (! empty($field['locale_based'])) {
            $info[] = $locale;
        }

        return empty($info) ? '' : '['.implode(' - ', $info).']';
    }
}

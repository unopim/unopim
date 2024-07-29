<?php

namespace Webkul\Product\Validator\Rule;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Webkul\Product\Type\AbstractType;

class ChannelLocalesRule implements ValidationRule
{
    /**
     * create validation rule object
     */
    public function __construct(protected array $channels) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $attribute = str_replace(AbstractType::CHANNEL_LOCALE_VALUES_KEY.'.', '', $attribute);

        if (! isset($attribute, $this->channels[$attribute])) {
            $fail(sprintf('Unexpected Channel %s', $attribute));

            return;
        }

        $locales = array_keys($value);

        $unexpected = array_diff($locales, $this->channels[$attribute]);

        if (! empty($unexpected)) {
            $fail(sprintf('Unexpected locale(s) %s in channel %s', implode($unexpected), $attribute));
        }
    }
}

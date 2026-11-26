<?php

declare(strict_types=1);

namespace Webkul\Product\Templates;

use Illuminate\Support\Str;
use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Attribute\Contracts\AttributeOption;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Services\AttributeNormalizerFactory;
use Webkul\Core\Repositories\ChannelRepository;

class TemplateValueFormatter
{
    public const array SUPPORTED_TYPES = [
        Attribute::TEXT_TYPE,
        Attribute::TEXTAREA_TYPE,
        Attribute::PRICE_FIELD_TYPE,
        Attribute::BOOLEAN_FIELD_TYPE,
        Attribute::SELECT_FIELD_TYPE,
        Attribute::MULTISELECT_FIELD_TYPE,
        Attribute::CHECKBOX_FIELD_TYPE,
        Attribute::DATE_FIELD_TYPE,
        Attribute::DATETIME_FIELD_TYPE,
        Attribute::MEASUREMENT_FIELD_TYPE,
    ];

    private const string BLOCK_BOUNDARY_PATTERN = '/<br\s*\/?>|<\/(?:p|li|h[1-6]|td|th|tr)>/i';

    private array $channelCurrencies = [];

    private array $optionLabels = [];

    /**
     * Create a new template value formatter instance.
     */
    public function __construct(
        protected AttributeNormalizerFactory $normalizerFactory,
        protected ChannelRepository $channelRepository,
    ) {}

    /**
     * Determine if the attribute type can be used as a template variable.
     */
    public function supports(AttributeContract $attribute): bool
    {
        return in_array($attribute->type, self::SUPPORTED_TYPES, true);
    }

    /**
     * Format the attribute value as text for a template, or return null when it is empty.
     */
    public function format(AttributeContract $attribute, mixed $value, string $channel, string $locale, ?string $currency = null): ?string
    {
        if (! is_filled_value($value)) {
            return null;
        }

        $formatted = match ($attribute->type) {
            Attribute::PRICE_FIELD_TYPE       => $this->formatPrice($value, $channel, $currency),
            Attribute::BOOLEAN_FIELD_TYPE     => $this->formatBoolean($value, $locale),
            Attribute::MEASUREMENT_FIELD_TYPE => $this->formatMeasurement($attribute, $value, $locale),
            Attribute::SELECT_FIELD_TYPE,
            Attribute::MULTISELECT_FIELD_TYPE,
            Attribute::CHECKBOX_FIELD_TYPE    => $this->formatOptions($attribute, $value, $locale),
            default                           => $this->formatText($attribute, $value),
        };

        return is_filled_value($formatted) ? $formatted : null;
    }

    /**
     * Convert HTML into plain text, keeping a space between block elements.
     */
    public function toPlainText(string $html): string
    {
        $text = preg_replace(self::BLOCK_BOUNDARY_PATTERN, ' ', $html) ?? $html;

        return Str::squish(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Get the currency codes assigned to the given channel.
     */
    public function channelCurrencies(string $channel): array
    {
        return $this->channelCurrencies[$channel] ??= $this->channelRepository
            ->findOneByField('code', $channel)
            ?->currencies
            ->pluck('code')
            ->all() ?? [];
    }

    private function formatText(AttributeContract $attribute, mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        return $attribute->isRichText() ? $this->toPlainText((string) $value) : (string) $value;
    }

    private function formatPrice(mixed $value, string $channel, ?string $currency): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        $currencies = $currency !== null
            ? [$currency]
            : ($this->channelCurrencies($channel) ?: array_keys($value));

        $amounts = array_filter(
            array_map(
                fn (string $code): ?string => is_filled_value($value[$code] ?? null) ? $value[$code].' '.$code : null,
                $currencies,
            ),
        );

        return implode(', ', $amounts);
    }

    private function formatBoolean(mixed $value, string $locale): string
    {
        $flag = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return match ($flag) {
            true    => trans('admin::app.common.yes', [], $locale),
            false   => trans('admin::app.common.no', [], $locale),
            default => (string) $value,
        };
    }

    private function formatMeasurement(AttributeContract $attribute, mixed $value, string $locale): ?string
    {
        $formatted = $this->normalizerFactory
            ->getNormalizer($attribute->type)
            ->normalize($value, $attribute, ['format' => 'datagrid', 'locale' => $locale]);

        return is_scalar($formatted) ? (string) $formatted : null;
    }

    private function formatOptions(AttributeContract $attribute, mixed $value, string $locale): string
    {
        $codes = array_values(array_filter(
            array_map(trim(...), is_array($value) ? array_map(strval(...), $value) : explode(',', (string) $value)),
            fn (string $code): bool => $code !== '',
        ));

        $labels = $this->optionLabels($attribute, $codes);

        return implode(', ', array_map(
            fn (string $code): string => filled($labels[$code][$locale] ?? null) ? $labels[$code][$locale] : $code,
            $codes,
        ));
    }

    private function optionLabels(AttributeContract $attribute, array $codes): array
    {
        $cached = $this->optionLabels[$attribute->id] ?? [];

        $missing = array_values(array_diff($codes, array_keys($cached)));

        if ($missing !== []) {
            $cached += array_fill_keys($missing, []);

            $attribute->options()
                ->whereIn('code', $missing)
                ->with('translations')
                ->get()
                ->each(function (AttributeOption $option) use (&$cached): void {
                    $cached[(string) $option->code] = $option->translations->pluck('label', 'locale')->all();
                });

            $this->optionLabels[$attribute->id] = $cached;
        }

        return $cached;
    }
}

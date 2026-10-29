<?php

namespace Webkul\CalculatedAttribute\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A lightweight view of the attribute table: which attributes exist, which are
 * calculated, and how each one scopes its value.
 *
 * Deliberately not Eloquent. The export loop reads this per product per
 * channel-locale pair, and Astrotomic's translatable models resolve config() on
 * every property read — the same trap documented in the product Exporter, which
 * precomputes its own flat attribute metadata for exactly this reason.
 */
class AttributeRegistry
{
    public const TYPE = 'calculated';

    /**
     * code => ['type' => string, 'formula' => ?string, 'per_locale' => bool, 'per_channel' => bool]
     *
     * @var array<string, array{type: string, formula: string|null, per_locale: bool, per_channel: bool}>|null
     */
    protected ?array $attributes = null;

    /**
     * Every attribute code, calculated or not.
     *
     * @return array<int, string>
     */
    public function codes(): array
    {
        return array_keys($this->all());
    }

    /**
     * Codes of attributes of type `calculated`, in a stable order.
     *
     * @return array<int, string>
     */
    public function calculatedCodes(): array
    {
        return array_keys(array_filter(
            $this->all(),
            fn (array $meta): bool => $meta['type'] === self::TYPE
        ));
    }

    public function isCalculated(string $code): bool
    {
        return ($this->all()[$code]['type'] ?? null) === self::TYPE;
    }

    public function exists(string $code): bool
    {
        return isset($this->all()[$code]);
    }

    public function formula(string $code): ?string
    {
        return $this->all()[$code]['formula'] ?? null;
    }

    /**
     * @return array<string, array{type: string, formula: string|null, per_locale: bool, per_channel: bool}>
     */
    public function all(): array
    {
        if ($this->attributes !== null) {
            return $this->attributes;
        }

        /*
         * Guard the column rather than the table: the attribute type is
         * registered by this package's service provider, so a boot that happens
         * before this package's migration has run (a fresh install, or an
         * upgrade mid-deploy) must degrade to "no calculated attributes" instead
         * of throwing on every product page.
         */
        if (! Schema::hasColumn('attributes', 'formula')) {
            return $this->attributes = [];
        }

        $attributes = [];

        $rows = DB::table('attributes')
            ->select(['code', 'type', 'formula', 'value_per_locale', 'value_per_channel'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $attributes[(string) $row->code] = [
                'type'        => (string) $row->type,
                'formula'     => $row->formula === null ? null : (string) $row->formula,
                'per_locale'  => (bool) $row->value_per_locale,
                'per_channel' => (bool) $row->value_per_channel,
            ];
        }

        return $this->attributes = $attributes;
    }

    /**
     * Read an attribute's value out of a product `values` array, honouring the
     * attribute's own scope flags.
     *
     * Mirrors Attribute::getValueFromProductValues() without needing the model.
     *
     * @param  array<string, mixed>  $values  variant-resolved product values
     */
    public function valueFrom(array $values, string $code, ?string $channel, ?string $locale): mixed
    {
        $meta = $this->all()[$code] ?? null;

        if ($meta === null) {
            return null;
        }

        if ($meta['per_locale'] && $meta['per_channel']) {
            return $values['channel_locale_specific'][$channel][$locale][$code] ?? null;
        }

        if ($meta['per_channel']) {
            return $values['channel_specific'][$channel][$code] ?? null;
        }

        if ($meta['per_locale']) {
            return $values['locale_specific'][$locale][$code] ?? null;
        }

        return $values['common'][$code] ?? null;
    }

    /**
     * Write a computed value into a product `values` array under the scope the
     * attribute declares.
     *
     * @param  array<string, mixed>  $values
     */
    public function placeValue(array &$values, string $code, mixed $value, ?string $channel, ?string $locale): void
    {
        $meta = $this->all()[$code] ?? null;

        if ($meta === null) {
            return;
        }

        if ($meta['per_locale'] && $meta['per_channel']) {
            $values['channel_locale_specific'][$channel][$locale][$code] = $value;

            return;
        }

        if ($meta['per_channel']) {
            $values['channel_specific'][$channel][$code] = $value;

            return;
        }

        if ($meta['per_locale']) {
            $values['locale_specific'][$locale][$code] = $value;

            return;
        }

        $values['common'][$code] = $value;
    }

    /**
     * Drop the cached snapshot. Needed after an attribute is created or edited
     * within the same request, and by tests.
     */
    public function flush(): void
    {
        $this->attributes = null;
    }
}

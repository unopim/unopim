<?php

namespace Webkul\CalculatedAttribute\Services;

use Illuminate\Support\Facades\Schema;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;

/**
 * A lightweight view of the attribute table: which attributes exist, which are
 * calculated, and how each one scopes its value.
 *
 * The rows are read once through the repository and flattened into plain
 * arrays. The export loop reads this per product per channel-locale pair, and
 * Astrotomic's translatable models resolve configuration on every property
 * read — the same trap documented in the product Exporter, which precomputes
 * its own flat attribute metadata for exactly this reason.
 *
 * The snapshot lives no longer than one request or one queued job: the binding
 * is `scoped()`, so Octane and the queue worker discard it between units of
 * work rather than serving yesterday's attribute list. Within a request, a save
 * invalidates it through the `catalog.attribute.*` events.
 */
class AttributeRegistry
{
    /**
     * code => ['type' => string, 'formula' => ?string, 'per_locale' => bool, 'per_channel' => bool]
     *
     * @var array<string, array{type: string, formula: string|null, per_locale: bool, per_channel: bool}>|null
     */
    protected ?array $attributes = null;

    /**
     * @var array<int, string>|null
     */
    protected ?array $allowedNames = null;

    /**
     * Incremented on every flush. Anything caching work derived from the
     * snapshot compares this instead of subscribing to events of its own.
     */
    protected int $version = 0;

    public function __construct(
        protected AttributeRepository $attributeRepository,
    ) {}

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
            fn (array $meta): bool => $meta['type'] === Attribute::CALCULATED_FIELD_TYPE
        ));
    }

    public function isCalculated(string $code): bool
    {
        return ($this->all()[$code]['type'] ?? null) === Attribute::CALCULATED_FIELD_TYPE;
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
     * Every identifier a formula may reference: all attribute codes that are
     * valid expression identifiers, plus the system variables.
     *
     * Computed once per snapshot rather than once per evaluation. Codes that
     * are not valid identifiers (an attribute code may legally start with a
     * digit) are dropped, because the grammar could never name them anyway.
     *
     * @return array<int, string>
     */
    public function allowedNames(): array
    {
        if ($this->allowedNames !== null) {
            return $this->allowedNames;
        }

        $codes = array_filter($this->codes(), static fn (string $code): bool => preg_match(
            '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/',
            $code
        ) === 1);

        return $this->allowedNames = array_values(array_unique(array_merge(
            array_values($codes),
            FormulaValidator::SYSTEM_VARIABLES
        )));
    }

    /**
     * The identity of the current snapshot.
     */
    public function version(): int
    {
        return $this->version;
    }

    /**
     * Codes of the calculated attributes whose formula text mentions `$code`.
     *
     * Textual containment alone is not enough — `price` must not match
     * `price_net` — so the match is on a word boundary, and the caller confirms
     * the hit against the parsed dependency list.
     *
     * @return array<int, string>
     */
    public function formulasMentioning(string $code): array
    {
        if ($code === '') {
            return [];
        }

        $pattern = '/\b'.preg_quote($code, '/').'\b/';

        return array_keys(array_filter(
            $this->all(),
            fn (array $meta): bool => $meta['type'] === Attribute::CALCULATED_FIELD_TYPE
                && is_string($meta['formula'])
                && preg_match($pattern, $meta['formula']) === 1
        ));
    }

    /**
     * The whole snapshot, read on first use.
     *
     * The column is guarded rather than the table: the attribute type is
     * registered by this package's service provider, so a boot that happens
     * before this package's migration has run (a fresh install, or an upgrade
     * mid-deploy) degrades to "no calculated attributes" instead of throwing on
     * every product page.
     *
     * @return array<string, array{type: string, formula: string|null, per_locale: bool, per_channel: bool}>
     */
    public function all(): array
    {
        if ($this->attributes !== null) {
            return $this->attributes;
        }

        if (! Schema::hasColumn('attributes', 'formula')) {
            return $this->attributes = [];
        }

        $attributes = [];

        $rows = $this->attributeRepository
            ->orderBy('id')
            ->all(['code', 'type', 'formula', 'value_per_locale', 'value_per_channel']);

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
        $this->allowedNames = null;
        $this->version++;
    }
}

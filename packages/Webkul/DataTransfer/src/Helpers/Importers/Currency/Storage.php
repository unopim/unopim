<?php

namespace Webkul\DataTransfer\Helpers\Importers\Currency;

use Webkul\Core\Repositories\CurrencyRepository;

class Storage
{
    /**
     * Items contains code as key and currency id as value
     */
    protected array $items = [];

    /**
     * Columns which will be selected from database
     */
    protected array $selectColumns = [
        'id',
        'code',
    ];

    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(protected CurrencyRepository $currencyRepository) {}

    /**
     * Initialize storage
     */
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    /**
     * Load the Currencies
     */
    public function load(array $codes = []): void
    {
        $query = $this->currencyRepository->query()
            ->select($this->selectColumns);

        if (! empty($codes)) {
            $query->whereIn('code', $codes);
        }

        $currencies = $query->get();

        foreach ($currencies as $currency) {
            $this->set($currency->code, $currency->id);
        }
    }

    /**
     * Set Code information
     */
    public function set(string $code, int $id): self
    {
        $this->items[$code] = $id;

        return $this;
    }

    /**
     * Check if code exists
     */
    public function has(string $code): bool
    {
        return isset($this->items[$code]);
    }

    /**
     * Get currency Id by code
     */
    public function get(string $code): ?int
    {
        if (! $this->has($code)) {
            return null;
        }

        return $this->items[$code];
    }

    /**
     * Is storage is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }
}

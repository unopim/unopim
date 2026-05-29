<?php

namespace Webkul\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Contracts\Currency;
use Webkul\Core\Eloquent\Repository;

class CurrencyRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Currency::class;
    }

    /**
     * Create.
     */
    #[\Override]
    public function create(array $attributes): mixed
    {
        Event::dispatch('core.currency.create.before');

        if (empty($attributes['decimal'])) {
            $attributes['decimal'] = 0;
        }

        $currency = parent::create($attributes);

        Event::dispatch('core.currency.create.after', $currency);

        return $currency;
    }

    /**
     * Update.
     */
    #[\Override]
    public function update(array $attributes, $id): mixed
    {
        Event::dispatch('core.currency.update.before', $id);

        if (empty($attributes['decimal'])) {
            $attributes['decimal'] = 0;
        }

        $currency = parent::update($attributes, $id);

        Event::dispatch('core.currency.update.after', $currency);

        return $currency;
    }

    /**
     * Delete.
     *
     * @param  int  $id
     */
    #[\Override]
    public function delete($id): bool
    {
        Event::dispatch('core.currency.delete.before', $id);

        if ($this->model->count() == 1) {
            return false;
        }

        if ($this->model->destroy($id)) {
            Event::dispatch('core.currency.delete.after', $id);

            return true;
        }

        return false;
    }

    /**
     * Check the currency is linked to any channel or not by currency id
     */
    public function checkCurrencyBeingUsed(int $currencyId): bool
    {
        return $this->find($currencyId)?->isCurrencyBeingUsed() ?? false;
    }

    /**
     * Fetchs All active currencies
     */
    public function getActiveCurrencies(): mixed
    {
        return $this->where('status', 1)->orderBy('code')->get();
    }

    /**
     * This function returns a query builder instance for further manipulation of the currency model.
     *
     * @return Builder
     */
    public function queryBuilder(): static
    {
        return $this;

    }
}

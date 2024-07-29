<?php

namespace Webkul\Admin\Helpers\Reporting;

use Webkul\Core\Repositories\CurrencyRepository;

class Currency extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected CurrencyRepository $currencyRepository,
    ) {}

    /**
     * This method calculates and returns the total number of currencies in the system.
     *
     * @return int The total number of currencies.
     */
    public function getTotalActiveCurrencies(): int
    {
        return $this->currencyRepository
            ->resetModel()
            ->where('status', 1)
            ->count();
    }
}

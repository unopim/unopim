<?php

namespace Webkul\Admin\Helpers\Reporting;

use Webkul\Core\Repositories\LocaleRepository;

class Locale extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected LocaleRepository $LocaleRepository,
    ) {}

    /**
     * This method calculates and returns the total number of locales in the system.
     *
     * @return int The total number of locales.
     */
    public function getTotalActiveLocales(): int
    {
        return $this->LocaleRepository
            ->resetModel()
            ->where('status', 1)
            ->count();
    }
}

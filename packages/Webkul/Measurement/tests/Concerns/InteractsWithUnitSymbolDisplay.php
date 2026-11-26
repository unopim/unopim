<?php

namespace Webkul\Measurement\Tests\Concerns;

use Illuminate\Support\Facades\Cache;
use Webkul\Core\Models\CoreConfig;

trait InteractsWithUnitSymbolDisplay
{
    /**
     * Save the display unit symbol setting as on.
     */
    public function enableUnitSymbolDisplay(): void
    {
        $this->setUnitSymbolDisplay(true);
    }

    /**
     * Save the display unit symbol setting as off.
     */
    public function disableUnitSymbolDisplay(): void
    {
        $this->setUnitSymbolDisplay(false);
    }

    /**
     * Persist the display unit symbol setting and drop cached config reads.
     */
    protected function setUnitSymbolDisplay(bool $enabled): void
    {
        CoreConfig::updateOrCreate(
            ['code' => 'system.measurement.display_unit_symbol'],
            ['value' => $enabled ? '1' : '0']
        );

        Cache::flush();
    }
}

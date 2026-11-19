<?php

namespace Webkul\Completeness\Helpers;

class Threshold
{
    public const DEFAULT_GREEN_FROM = 90;

    public const DEFAULT_RED_BELOW = 60;

    /**
     * Green-from and red-below bounds for the completeness colours.
     *
     * @return array{int, int}
     */
    public function bounds(): array
    {
        $greenFrom = $this->configValue('system.completeness.green_from', self::DEFAULT_GREEN_FROM);
        $redBelow = $this->configValue('system.completeness.red_below', self::DEFAULT_RED_BELOW);

        if ($greenFrom <= $redBelow) {
            return [self::DEFAULT_GREEN_FROM, self::DEFAULT_RED_BELOW];
        }

        return [$greenFrom, $redBelow];
    }

    /**
     * Read a threshold clamped to 0..100, or the default when it is not numeric.
     */
    protected function configValue(string $key, int $default): int
    {
        $value = core()->getConfigData($key);

        return is_numeric($value) ? max(0, min(100, (int) $value)) : $default;
    }
}

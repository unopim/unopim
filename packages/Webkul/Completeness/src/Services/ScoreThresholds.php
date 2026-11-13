<?php

namespace Webkul\Completeness\Services;

use Webkul\Completeness\Enums\ScoreLevel;

class ScoreThresholds
{
    public const GREEN_FROM_CODE = 'system.completeness.green_from';

    public const RED_BELOW_CODE = 'system.completeness.red_below';

    public const DEFAULT_GREEN_FROM = 80;

    public const DEFAULT_RED_BELOW = 50;

    /**
     * Score at or above which completeness is shown in the success colour.
     */
    public function greenFrom(): float
    {
        return $this->resolve()['greenFrom'];
    }

    /**
     * Score below which completeness is shown in the danger colour.
     */
    public function redBelow(): float
    {
        return $this->resolve()['redBelow'];
    }

    /**
     * The configured thresholds, shaped for the admin Vue components.
     *
     * @return array{greenFrom: float, redBelow: float}
     */
    public function toArray(): array
    {
        return $this->resolve();
    }

    /**
     * Colour level for a completeness score.
     */
    public function level(float|int $score): ScoreLevel
    {
        $thresholds = $this->resolve();

        return match (true) {
            $score >= $thresholds['greenFrom'] => ScoreLevel::Success,
            $score < $thresholds['redBelow']   => ScoreLevel::Danger,
            default                            => ScoreLevel::Warning,
        };
    }

    /**
     * Read both thresholds, each falling back to its default when unset or out
     * of range, and both falling back to the defaults when no longer ordered.
     *
     * @return array{greenFrom: float, redBelow: float}
     */
    protected function resolve(): array
    {
        $greenFrom = $this->read(self::GREEN_FROM_CODE) ?? (float) self::DEFAULT_GREEN_FROM;
        $redBelow = $this->read(self::RED_BELOW_CODE) ?? (float) self::DEFAULT_RED_BELOW;

        if ($greenFrom <= $redBelow) {
            return [
                'greenFrom' => (float) self::DEFAULT_GREEN_FROM,
                'redBelow'  => (float) self::DEFAULT_RED_BELOW,
            ];
        }

        return [
            'greenFrom' => $greenFrom,
            'redBelow'  => $redBelow,
        ];
    }

    protected function read(string $code): ?float
    {
        $value = core()->getConfigData($code);

        if (! is_numeric($value) || $value < 0 || $value > 100) {
            return null;
        }

        return (float) $value;
    }
}

<?php

namespace Webkul\Pricing\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Immutable value object representing the result of a margin validation check.
 *
 * Produced by MarginProtector::validate(). Indicates whether a proposed price
 * meets, warns about, or violates the configured margin thresholds.
 */
final class MarginValidationResult implements Arrayable, JsonSerializable
{
    public const STATUS_OK      = 'ok';

    public const STATUS_WARNING = 'warning';

    public const STATUS_BLOCKED = 'blocked';

    /**
     * @param  string     $status                  One of: ok, warning, blocked.
     * @param  float      $proposedPrice           The price being validated.
     * @param  float      $breakEvenPrice          The computed break-even price.
     * @param  float      $minimumMarginPrice      The price needed to achieve minimum margin.
     * @param  float      $targetMarginPrice       The price needed to achieve target margin.
     * @param  float      $actualMarginPercentage  The margin% the proposed price would yield.
     * @param  float      $minimumMarginPercentage The configured minimum margin threshold.
     * @param  int|null   $eventId                 The ID of any MarginProtectionEvent created.
     * @param  string     $message                 Human-readable explanation.
     */
    public function __construct(
        public readonly string $status,
        public readonly float $proposedPrice,
        public readonly float $breakEvenPrice,
        public readonly float $minimumMarginPrice,
        public readonly float $targetMarginPrice,
        public readonly float $actualMarginPercentage,
        public readonly float $minimumMarginPercentage,
        public readonly ?int $eventId,
        public readonly string $message,
    ) {}

    /**
     * Whether this validation result represents a hard block.
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * Whether this validation result represents a soft warning.
     */
    public function isWarning(): bool
    {
        return $this->status === self::STATUS_WARNING;
    }

    /**
     * Whether the proposed price passes all margin checks.
     */
    public function isOk(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status'                    => $this->status,
            'proposed_price'            => round($this->proposedPrice, 4),
            'break_even_price'          => round($this->breakEvenPrice, 4),
            'minimum_margin_price'      => round($this->minimumMarginPrice, 4),
            'target_margin_price'       => round($this->targetMarginPrice, 4),
            'actual_margin_percentage'  => round($this->actualMarginPercentage, 2),
            'minimum_margin_percentage' => round($this->minimumMarginPercentage, 2),
            'event_id'                  => $this->eventId,
            'message'                   => $this->message,
        ];
    }

    /**
     * Serialize for JSON encoding.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

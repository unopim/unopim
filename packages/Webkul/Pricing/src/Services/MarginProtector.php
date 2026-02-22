<?php

namespace Webkul\Pricing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Pricing\Events\MarginApproved;
use Webkul\Pricing\Events\MarginBlocked;
use Webkul\Pricing\Models\MarginProtectionEvent;
use Webkul\Pricing\Models\PricingStrategy;
use Webkul\Pricing\Repositories\MarginProtectionEventRepository;
use Webkul\Pricing\Repositories\PricingStrategyRepository;
use Webkul\Pricing\ValueObjects\MarginValidationResult;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Validates proposed prices against configured margin thresholds and manages
 * margin protection events (approvals, rejections, expiration).
 *
 * This service is the gatekeeper that prevents products from being listed at
 * prices below acceptable profit margins. When a price violates margin rules,
 * a MarginProtectionEvent is created and must be explicitly approved by an
 * authorized user before the price can be applied.
 *
 * All monetary calculations use BCMath for precision (scale=4 for prices, scale=2 for percentages).
 */
class MarginProtector
{
    /**
     * BCMath scale for price calculations.
     */
    protected const PRICE_SCALE = 4;

    /**
     * BCMath scale for percentage calculations.
     */
    protected const PERCENT_SCALE = 2;

    /**
     * Number of days an approved margin exception remains valid.
     */
    protected const APPROVAL_EXPIRY_DAYS = 30;

    public function __construct(
        protected BreakEvenCalculator $breakEvenCalculator,
        protected PricingStrategyRepository $pricingStrategyRepository,
        protected MarginProtectionEventRepository $marginProtectionEventRepository,
        protected ProductRepository $productRepository,
    ) {}

    /**
     * Validate a proposed price against margin rules for a product.
     *
     * Compares the proposed price to break-even, minimum margin, and target margin
     * thresholds derived from the applicable PricingStrategy.
     *
     * @param  int          $productId      Product to validate the price for.
     * @param  float        $proposedPrice  The selling price to validate.
     * @param  int|null     $channelId      Optional channel context for strategy resolution.
     * @param  string|null  $currency       Currency code override.
     * @return MarginValidationResult       Validation outcome with status, prices, and event details.
     */
    public function validate(int $productId, float $proposedPrice, ?int $channelId = null, ?string $currency = null): MarginValidationResult
    {
        // 1. Check for an existing active approval that overrides margin checks
        if ($this->hasActiveApproval($productId, $channelId)) {
            $breakEven = $this->breakEvenCalculator->calculate($productId, $channelId, $currency);
            $strategy = $this->resolveStrategy($productId, $channelId);

            $minimumMarginPrice = $this->priceForMargin($breakEven->breakEvenPrice, (float) $strategy->minimum_margin_percentage);
            $targetMarginPrice = $this->priceForMargin($breakEven->breakEvenPrice, (float) $strategy->target_margin_percentage);
            $actualMargin = $this->computeMarginPercentage($proposedPrice, $breakEven->breakEvenPrice);

            return new MarginValidationResult(
                status: MarginValidationResult::STATUS_OK,
                proposedPrice: $proposedPrice,
                breakEvenPrice: $breakEven->breakEvenPrice,
                minimumMarginPrice: $minimumMarginPrice,
                targetMarginPrice: $targetMarginPrice,
                actualMarginPercentage: $actualMargin,
                minimumMarginPercentage: (float) $strategy->minimum_margin_percentage,
                eventId: null,
                message: 'Price approved via active margin exception.',
            );
        }

        // 2. Calculate break-even for this product/channel
        $breakEven = $this->breakEvenCalculator->calculate($productId, $channelId, $currency);

        // 3. Resolve the applicable pricing strategy
        $strategy = $this->resolveStrategy($productId, $channelId);

        // 4. Compute threshold prices
        $minimumMarginPrice = $this->priceForMargin($breakEven->breakEvenPrice, (float) $strategy->minimum_margin_percentage);
        $targetMarginPrice = $this->priceForMargin($breakEven->breakEvenPrice, (float) $strategy->target_margin_percentage);

        // 5. Compute actual margin the proposed price would yield
        $actualMargin = $this->computeMarginPercentage($proposedPrice, $breakEven->breakEvenPrice);

        // 6. Determine validation status
        if ($proposedPrice < $breakEven->breakEvenPrice) {
            // Below break-even: guaranteed loss
            return $this->createBlockedResult(
                $productId,
                $channelId,
                $proposedPrice,
                $breakEven->breakEvenPrice,
                $minimumMarginPrice,
                $targetMarginPrice,
                $actualMargin,
                (float) $strategy->minimum_margin_percentage,
                $breakEven->currency,
                'Price is below break-even and would result in a loss.'
            );
        }

        if ($proposedPrice < $minimumMarginPrice) {
            // Below minimum margin: blocked
            return $this->createBlockedResult(
                $productId,
                $channelId,
                $proposedPrice,
                $breakEven->breakEvenPrice,
                $minimumMarginPrice,
                $targetMarginPrice,
                $actualMargin,
                (float) $strategy->minimum_margin_percentage,
                $breakEven->currency,
                sprintf(
                    'Price yields %.2f%% margin, below the minimum threshold of %.2f%%.',
                    $actualMargin,
                    $strategy->minimum_margin_percentage
                )
            );
        }

        if ($proposedPrice < $targetMarginPrice) {
            // Below target but above minimum: warning
            return $this->createWarningResult(
                $productId,
                $channelId,
                $proposedPrice,
                $breakEven->breakEvenPrice,
                $minimumMarginPrice,
                $targetMarginPrice,
                $actualMargin,
                (float) $strategy->minimum_margin_percentage,
                $breakEven->currency,
                sprintf(
                    'Price yields %.2f%% margin, which is below the target of %.2f%% but above the minimum.',
                    $actualMargin,
                    $strategy->target_margin_percentage
                )
            );
        }

        // Above target: OK
        Log::debug('Margin validation passed', [
            'product_id'     => $productId,
            'channel_id'     => $channelId,
            'proposed_price' => $proposedPrice,
            'actual_margin'  => $actualMargin,
        ]);

        return new MarginValidationResult(
            status: MarginValidationResult::STATUS_OK,
            proposedPrice: $proposedPrice,
            breakEvenPrice: $breakEven->breakEvenPrice,
            minimumMarginPrice: $minimumMarginPrice,
            targetMarginPrice: $targetMarginPrice,
            actualMarginPercentage: $actualMargin,
            minimumMarginPercentage: (float) $strategy->minimum_margin_percentage,
            eventId: null,
            message: sprintf('Price yields %.2f%% margin, meeting the target of %.2f%%.', $actualMargin, $strategy->target_margin_percentage),
        );
    }

    /**
     * Approve a blocked margin event, granting a time-limited exception.
     *
     * Only users with the 'catalog.pricing.margins.approve' permission should
     * call this method. Permission checking is enforced at the controller/policy level.
     *
     * @param  int          $eventId     The MarginProtectionEvent ID to approve.
     * @param  int          $approverId  The admin user ID granting approval.
     * @param  string|null  $reason      Optional justification for the approval.
     * @return MarginProtectionEvent      The updated event record.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If event not found.
     * @throws \LogicException If event is not in a state that can be approved.
     */
    public function approve(int $eventId, int $approverId, ?string $reason = null): MarginProtectionEvent
    {
        return DB::transaction(function () use ($eventId, $approverId, $reason) {
            $event = $this->marginProtectionEventRepository->findOrFail($eventId);

            if (! in_array($event->event_type, ['blocked', 'warning'])) {
                throw new \LogicException(
                    "Cannot approve event #{$eventId}: current status is '{$event->event_type}'. "
                    .'Only blocked or warning events can be approved.'
                );
            }

            $event->update([
                'event_type'  => 'approved',
                'approved_by' => $approverId,
                'approved_at' => now(),
                'expires_at'  => now()->addDays(self::APPROVAL_EXPIRY_DAYS),
                'reason'      => $reason ?? $event->reason,
            ]);

            $event->refresh();

            event(new MarginApproved($event, $approverId));

            Log::info('Margin exception approved', [
                'event_id'    => $eventId,
                'product_id'  => $event->product_id,
                'channel_id'  => $event->channel_id,
                'approved_by' => $approverId,
                'expires_at'  => $event->expires_at->toIso8601String(),
            ]);

            return $event;
        });
    }

    /**
     * Reject a margin exception request.
     *
     * Sets the event to 'rejected' state with the rejection rationale.
     *
     * @param  int     $eventId     The MarginProtectionEvent ID to reject.
     * @param  int     $approverId  The admin user ID rejecting the request.
     * @param  string  $reason      Mandatory reason for rejection.
     * @return MarginProtectionEvent The updated event record.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If event not found.
     */
    public function reject(int $eventId, int $approverId, string $reason): MarginProtectionEvent
    {
        $event = $this->marginProtectionEventRepository->findOrFail($eventId);

        $event->update([
            'reason'      => sprintf('[Rejected by admin #%d] %s', $approverId, $reason),
            'approved_by' => $approverId,
            'approved_at' => now(),
            'event_type'  => 'rejected',
        ]);

        $event->refresh();

        Log::info('Margin exception rejected', [
            'event_id'    => $eventId,
            'product_id'  => $event->product_id,
            'rejected_by' => $approverId,
            'reason'      => $reason,
        ]);

        return $event;
    }

    /**
     * Expire all margin approvals that have passed their expires_at timestamp.
     *
     * Intended to be called by the Laravel scheduler (daily).
     *
     * @return int The number of events that were expired.
     */
    public function expireStaleApprovals(): int
    {
        $staleEvents = $this->marginProtectionEventRepository->getModel()
            ->newQuery()
            ->where('event_type', 'approved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        if ($staleEvents->isEmpty()) {
            return 0;
        }

        $count = 0;

        DB::transaction(function () use ($staleEvents, &$count) {
            foreach ($staleEvents as $event) {
                $event->update(['event_type' => 'expired']);
                $count++;

                // Invalidate any cached break-even that relied on this approval
                $this->breakEvenCalculator->invalidateCache(
                    $event->product_id,
                    $event->channel_id
                );
            }
        });

        Log::info('Stale margin approvals expired', ['count' => $count]);

        return $count;
    }

    /**
     * Check if a product currently has an active (approved, non-expired) margin exception.
     *
     * @param  int       $productId  Product to check.
     * @param  int|null  $channelId  Optional channel scope.
     * @return bool
     */
    public function hasActiveApproval(int $productId, ?int $channelId = null): bool
    {
        return $this->marginProtectionEventRepository->getModel()
            ->newQuery()
            ->where('product_id', $productId)
            ->where('event_type', 'approved')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->when(! is_null($channelId), function ($q) use ($channelId) {
                $q->where('channel_id', $channelId);
            })
            ->exists();
    }

    /**
     * Create a BLOCKED validation result and persist a MarginProtectionEvent.
     */
    protected function createBlockedResult(
        int $productId,
        ?int $channelId,
        float $proposedPrice,
        float $breakEvenPrice,
        float $minimumMarginPrice,
        float $targetMarginPrice,
        float $actualMargin,
        float $minimumMarginPercentage,
        string $currency,
        string $message,
    ): MarginValidationResult {
        $event = $this->persistProtectionEvent(
            $productId,
            $channelId,
            'blocked',
            $proposedPrice,
            $breakEvenPrice,
            $minimumMarginPrice,
            $targetMarginPrice,
            $actualMargin,
            $minimumMarginPercentage,
            $currency,
            $message
        );

        event(new MarginBlocked($event));

        Log::warning('Margin validation BLOCKED', [
            'product_id'     => $productId,
            'channel_id'     => $channelId,
            'proposed_price' => $proposedPrice,
            'break_even'     => $breakEvenPrice,
            'actual_margin'  => $actualMargin,
            'event_id'       => $event->id,
        ]);

        return new MarginValidationResult(
            status: MarginValidationResult::STATUS_BLOCKED,
            proposedPrice: $proposedPrice,
            breakEvenPrice: $breakEvenPrice,
            minimumMarginPrice: $minimumMarginPrice,
            targetMarginPrice: $targetMarginPrice,
            actualMarginPercentage: $actualMargin,
            minimumMarginPercentage: $minimumMarginPercentage,
            eventId: $event->id,
            message: $message,
        );
    }

    /**
     * Create a WARNING validation result and persist a MarginProtectionEvent.
     */
    protected function createWarningResult(
        int $productId,
        ?int $channelId,
        float $proposedPrice,
        float $breakEvenPrice,
        float $minimumMarginPrice,
        float $targetMarginPrice,
        float $actualMargin,
        float $minimumMarginPercentage,
        string $currency,
        string $message,
    ): MarginValidationResult {
        $event = $this->persistProtectionEvent(
            $productId,
            $channelId,
            'warning',
            $proposedPrice,
            $breakEvenPrice,
            $minimumMarginPrice,
            $targetMarginPrice,
            $actualMargin,
            $minimumMarginPercentage,
            $currency,
            $message
        );

        Log::notice('Margin validation WARNING', [
            'product_id'     => $productId,
            'channel_id'     => $channelId,
            'proposed_price' => $proposedPrice,
            'actual_margin'  => $actualMargin,
            'event_id'       => $event->id,
        ]);

        return new MarginValidationResult(
            status: MarginValidationResult::STATUS_WARNING,
            proposedPrice: $proposedPrice,
            breakEvenPrice: $breakEvenPrice,
            minimumMarginPrice: $minimumMarginPrice,
            targetMarginPrice: $targetMarginPrice,
            actualMarginPercentage: $actualMargin,
            minimumMarginPercentage: $minimumMarginPercentage,
            eventId: $event->id,
            message: $message,
        );
    }

    /**
     * Persist a margin protection event to the database.
     */
    protected function persistProtectionEvent(
        int $productId,
        ?int $channelId,
        string $eventType,
        float $proposedPrice,
        float $breakEvenPrice,
        float $minimumMarginPrice,
        float $targetMarginPrice,
        float $actualMargin,
        float $minimumMarginPercentage,
        string $currency,
        string $reason,
    ): MarginProtectionEvent {
        return $this->marginProtectionEventRepository->create([
            'product_id'               => $productId,
            'channel_id'               => $channelId,
            'event_type'               => $eventType,
            'proposed_price'           => $proposedPrice,
            'break_even_price'         => $breakEvenPrice,
            'minimum_margin_price'     => $minimumMarginPrice,
            'target_margin_price'      => $targetMarginPrice,
            'currency_code'            => $currency,
            'margin_percentage'        => $actualMargin,
            'minimum_margin_percentage' => $minimumMarginPercentage,
            'reason'                   => $reason,
        ]);
    }

    /**
     * Resolve the applicable PricingStrategy for a product/channel combination.
     *
     * Falls back to a sensible default strategy if none is configured.
     */
    protected function resolveStrategy(int $productId, ?int $channelId): PricingStrategy
    {
        $strategy = $this->pricingStrategyRepository->resolveForProduct($productId, $channelId);

        if ($strategy) {
            return $strategy;
        }

        // Return a default in-memory strategy with standard margins
        Log::notice('No pricing strategy found, using defaults', [
            'product_id' => $productId,
            'channel_id' => $channelId,
        ]);

        return new PricingStrategy([
            'scope_type'                => 'global',
            'minimum_margin_percentage' => 15.00,
            'target_margin_percentage'  => 25.00,
            'premium_margin_percentage' => 40.00,
            'psychological_pricing'     => true,
            'round_to'                  => '0.99',
            'is_active'                 => true,
            'priority'                  => 0,
        ]);
    }

    /**
     * Calculate the price needed to achieve a given margin percentage above break-even.
     *
     * Formula: price = breakEven / (1 - margin% / 100)
     *
     * @param  float  $breakEvenPrice     The break-even price.
     * @param  float  $marginPercentage   Desired margin as a percentage (e.g. 25.0 = 25%).
     * @return float  The selling price that yields the specified margin.
     */
    protected function priceForMargin(float $breakEvenPrice, float $marginPercentage): float
    {
        if ($marginPercentage >= 100) {
            // Margin >= 100% is theoretically impossible
            return PHP_FLOAT_MAX;
        }

        if ($breakEvenPrice <= 0) {
            return 0.0;
        }

        $marginDecimal = bcdiv((string) $marginPercentage, '100', self::PRICE_SCALE);
        $denominator = bcsub('1', $marginDecimal, self::PRICE_SCALE);
        $result = bcdiv((string) $breakEvenPrice, $denominator, self::PRICE_SCALE);

        return (float) $result;
    }

    /**
     * Compute the actual margin percentage given a selling price and break-even cost.
     *
     * Formula: margin% = ((price - breakEven) / price) * 100
     *
     * @param  float  $price         The proposed selling price.
     * @param  float  $breakEvenPrice The break-even cost.
     * @return float  The margin percentage (can be negative if price < breakEven).
     */
    protected function computeMarginPercentage(float $price, float $breakEvenPrice): float
    {
        if ($price <= 0) {
            return -100.0;
        }

        $diff = bcsub((string) $price, (string) $breakEvenPrice, self::PRICE_SCALE);
        $ratio = bcdiv($diff, (string) $price, self::PRICE_SCALE);
        $result = bcmul($ratio, '100', self::PERCENT_SCALE);

        return (float) $result;
    }
}

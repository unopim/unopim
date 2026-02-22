<?php

namespace Webkul\Order\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Webkul\Order\Events\OrderSynced;

/**
 * Update Order Profitability Listener
 *
 * Calculates and stores profitability metrics after order sync.
 * Runs asynchronously in the queue for performance.
 */
class UpdateOrderProfitability implements ShouldQueue
{
    /**
     * The name of the queue connection to use.
     *
     * @var string
     */
    public $connection = 'database';

    /**
     * The name of the queue to use.
     *
     * @var string
     */
    public $queue = 'default';

    /**
     * Handle the event.
     *
     * @param  OrderSynced  $event
     * @return void
     */
    public function handle(OrderSynced $event): void
    {
        $order = $event->order;

        // Calculate cost from order items
        $totalCost = 0;

        foreach ($order->items as $item) {
            if ($item->product) {
                $values = $item->product->values;
                $productCost = (float) ($values['common']['cost'] ?? 0);
                $totalCost += $productCost * $item->quantity;
            }
        }

        // Calculate profitability metrics
        $revenue = (float) $order->total_amount;
        $profit = $revenue - $totalCost;
        $marginPercentage = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        // Store profitability data in additional_data JSON column
        $additionalData = $order->additional_data ?? [];
        $additionalData['profitability'] = [
            'cost' => round($totalCost, 2),
            'profit' => round($profit, 2),
            'margin_percentage' => round($marginPercentage, 2),
            'calculated_at' => now()->toIso8601String(),
        ];

        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'additional_data' => json_encode($additionalData),
            ]);
    }

    /**
     * Determine whether the listener should be queued.
     *
     * @param  OrderSynced  $event
     * @return bool
     */
    public function shouldQueue(OrderSynced $event): bool
    {
        return true;
    }
}

<?php

namespace Webkul\Order\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Webkul\Order\Events\OrderSynced;

/**
 * Update Product Sales Metrics Listener
 *
 * Updates product sales statistics after order sync.
 * Tracks total quantity sold and revenue per product.
 */
class UpdateProductSalesMetrics implements ShouldQueue
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

        // Skip if order is cancelled or refunded
        if (in_array($order->status, ['cancelled', 'refunded'])) {
            return;
        }

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            // Get product
            $product = DB::table('products')->find($item->product_id);

            if (! $product) {
                continue;
            }

            // Get current values
            $values = json_decode($product->values, true) ?? [];

            // Initialize sales metrics if not exists
            if (! isset($values['common']['sales_metrics'])) {
                $values['common']['sales_metrics'] = [
                    'total_quantity_sold' => 0,
                    'total_revenue' => 0,
                    'last_sale_date' => null,
                ];
            }

            // Update metrics
            if ($event->isNew) {
                // Only count new orders
                $values['common']['sales_metrics']['total_quantity_sold'] += $item->quantity;
                $values['common']['sales_metrics']['total_revenue'] += $item->total;
            }

            $values['common']['sales_metrics']['last_sale_date'] = $order->order_date;

            // Update product
            DB::table('products')
                ->where('id', $item->product_id)
                ->update([
                    'values' => json_encode($values),
                    'updated_at' => now(),
                ]);
        }
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

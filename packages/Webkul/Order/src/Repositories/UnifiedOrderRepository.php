<?php

namespace Webkul\Order\Repositories;

use Illuminate\Support\Collection;
use Webkul\Core\Eloquent\Repository;

class UnifiedOrderRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Order\Contracts\UnifiedOrder';
    }

    /**
     * Get recent orders.
     */
    public function getRecentOrders(int $limit = 10): Collection
    {
        return $this->model
            ->with(['channel', 'orderItems'])
            ->orderBy('ordered_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get orders by channel.
     */
    public function getOrdersByChannel(int $channelId): Collection
    {
        return $this->model
            ->where('channel_id', $channelId)
            ->with(['orderItems'])
            ->orderBy('ordered_at', 'desc')
            ->get();
    }

    /**
     * Calculate total revenue.
     */
    public function calculateTotalRevenue(array $filters = []): float
    {
        $query = $this->model->newQuery();

        if (isset($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('ordered_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('ordered_at', '<=', $filters['end_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return (float) $query->sum('total');
    }

    /**
     * Get profitable orders.
     */
    public function getProfitableOrders(): Collection
    {
        return $this->model
            ->profitable()
            ->with(['channel', 'orderItems'])
            ->orderBy('ordered_at', 'desc')
            ->get();
    }

    /**
     * Get orders with profit calculations.
     */
    public function getOrdersWithProfit(array $filters = []): Collection
    {
        $query = $this->model
            ->with(['channel', 'orderItems'])
            ->orderBy('ordered_at', 'desc');

        if (isset($filters['channel_id'])) {
            $query->where('channel_id', $filters['channel_id']);
        }

        if (isset($filters['start_date'])) {
            $query->where('ordered_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('ordered_at', '<=', $filters['end_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $orders = $query->get();

        return $orders->map(function ($order) {
            $profitability = $order->calculateProfitability();
            $order->profitability = $profitability;

            return $order;
        });
    }
}

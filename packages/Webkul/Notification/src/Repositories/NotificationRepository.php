<?php

namespace Webkul\Notification\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Notification\Contracts\Notification;

class NotificationRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Notification::class;
    }

    /**
     * Return Filtered Notification resources
     */
    public function getParamsData(array $params): array
    {
        $user = auth()->user();

        $query = $user->notifications()
            ->with('notification');

        if (isset($params['read'])) {
            $query->where('read', $params['read']);
        }

        $notifications = $query->latest()->paginate($params['limit'] ?? 10);

        $totalUnread = $user->notifications()->where('read', 0)->count();

        return ['notifications' => $notifications, 'total_unread' => $totalUnread];
    }

    /**
     * Return Notification resources
     */
    public function getAll(): array
    {
        $user = auth()->user();

        $query = $user->notifications()
            ->with('notification');

        $notifications = $query->latest()->paginate(10);

        $totalUnread = $user->notifications()->where('read', 0)->count();

        return ['notifications' => $notifications, 'total_unread' => $totalUnread];
    }
}

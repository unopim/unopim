<?php

namespace Webkul\Notification\Repositories;

use Webkul\Core\Eloquent\Repository;

class NotificationRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Notification\Contracts\Notification';
    }

    /**
     * Return Filtered Notification resources
     *
     * @param  array  $params
     * @return array
     */
    public function getParamsData($params)
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
     *
     * @return array
     */
    public function getAll()
    {
        $user = auth()->user();

        $query = $user->notifications()
            ->with('notification');

        $notifications = $query->latest()->paginate(10);

        $totalUnread = $user->notifications()->where('read', 0)->count();

        return ['notifications' => $notifications, 'total_unread' => $totalUnread];
    }
}

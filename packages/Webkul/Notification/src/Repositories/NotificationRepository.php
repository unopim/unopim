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
        if (isset($params['read']) && isset($params['limit'])) {
            $query->where('read', $params['read'])->limit($params['limit']);
        } elseif (isset($params['limit'])) {
            $query->limit($params['limit']);
        }

        $notifications = $query->latest()->paginate($params['limit'] ?? 10);

        $totalUnread = $query->where('read', '0')->count();

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

        $notifications = $query->latest()->paginate($params['limit'] ?? 10);

        $totalUnread = $query->count();

        return ['notifications' => $notifications, 'total_unread' => $totalUnread];
    }
}

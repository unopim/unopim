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
        $notifications = [];

        $statusCounts = [];

        return ['notifications' => $notifications, 'status_counts' => $statusCounts];
    }

    /**
     * Return Notification resources
     *
     * @return array
     */
    public function getAll()
    {

        $notifications = [];

        $statusCounts = [];

        return ['notifications' => $notifications, 'status_counts' => $statusCounts];
    }
}

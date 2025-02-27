<?php

namespace Webkul\Admin\Http\Controllers;

use Webkul\Notification\Repositories\NotificationRepository;

class NotificationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected NotificationRepository $notificationRepository) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin::notifications.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return array
     */
    public function getNotifications()
    {
        $params = request()->except('page');

        $searchResults = count($params)
            ? $this->notificationRepository->getParamsData($params)
            : $this->notificationRepository->getAll();

        $results = isset($searchResults['notifications']) ? $searchResults['notifications'] : $searchResults;

        $totalUnread = isset($searchResults['total_unread']) ? $searchResults['total_unread'] : 0;

        return [
            'search_results' => $results,
            'total_unread'   => $totalUnread,
        ];
    }

    /**
     * Update the notification is reade or not.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     *                               Returns a view for redirection if the notification has a route.//+
     *                               Returns a back redirect if no route is associated with the notification.//+
     *                               Returns a 404 error if the notification does not exist
     */
    public function viewedNotifications($id)
    {
        $notification = $this->notificationRepository->find($id);

        if ($notification) {
            $notification->userNotifications()
                ->where('read', 0)
                ->where('admin_id', auth()->user()->id)
                ->update(['read' => 1]);

            if ($notification->route) {
                return redirect()->route($notification->route, $notification->route_params);
            }

            return back();
        }

        abort(404);
    }

    /**
     * Update the notification is reade or not.
     *
     * @return array
     */
    public function readAllNotifications()
    {
        $user = auth()->user();
        $user->notifications()->where('read', 0)->update(['read' => 1]);

        $searchResults = $this->notificationRepository->getParamsData([
            'limit' => 5,
            'read'  => 0,
        ]);

        return [
            'search_results'  => $searchResults,
            'total_unread'    => $user->notifications()->where('read', 0)->count(),
            'success_message' => trans('admin::app.notifications.marked-success'),
        ];
    }
}

<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Notification\Repositories\NotificationRepository;

class NotificationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected NotificationRepository $notificationRepository) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('admin::notifications.index');
    }

    /**
     * Display a listing of the resource.
     */
    public function getNotifications(): array
    {
        $params = request()->except('page');

        $searchResults = count($params)
            ? $this->notificationRepository->getParamsData($params)
            : $this->notificationRepository->getAll();

        $results = $searchResults['notifications'] ?? $searchResults;

        $totalUnread = $searchResults['total_unread'] ?? 0;

        return [
            'search_results' => $results,
            'total_unread'   => $totalUnread,
        ];
    }

    /**
     * Mark notification as read and redirect to its route.
     */
    public function viewedNotifications(int $id): RedirectResponse
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
     */
    public function readAllNotifications(): array
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

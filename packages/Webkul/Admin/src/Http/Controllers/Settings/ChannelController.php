<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Settings\ChannelDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\ChannelForm;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;

class ChannelController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CategoryRepository $categoryRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(ChannelDataGrid::class)->toJson();
        }

        return view('admin::settings.channels.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $rootCategories = $this->categoryRepository->getRootCategories();

        return view('admin::settings.channels.create', compact('rootCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ChannelForm $request): RedirectResponse
    {
        $data = $request->validated();

        Event::dispatch('core.channel.create.before');

        $channel = $this->channelRepository->create($data);

        Event::dispatch('core.channel.create.after', $channel);

        session()->flash('success', trans('admin::app.settings.channels.create.create-success'));

        return redirect()->route('admin.settings.channels.edit', $channel->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $channel = $this->channelRepository->with(['locales', 'currencies'])->findOrFail($id);

        $rootCategories = $this->categoryRepository->getRootCategories();

        return view('admin::settings.channels.edit', compact('channel', 'rootCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ChannelForm $request, int $id): RedirectResponse
    {
        $data = $request->validated();

        Event::dispatch('core.channel.update.before', $id);

        $channel = $this->channelRepository->update($data, $id);

        Event::dispatch('core.channel.update.after', $channel);

        session()->flash('success', trans('admin::app.settings.channels.edit.update-success'));

        return redirect()->route('admin.settings.channels.edit', $channel->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $channel = $this->channelRepository->findOrFail($id);

        if ($channel->count() <= 1) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.channels.index.can-not-delete-error', ['channel' => $channel->code]),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($channel->code == config('app.channel')) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.channels.index.last-delete-error'),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            Event::dispatch('core.channel.delete.before', $id);

            $this->channelRepository->delete($id);

            Event::dispatch('core.channel.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.settings.channels.index.delete-success'),
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.channels.index.delete-failed'),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

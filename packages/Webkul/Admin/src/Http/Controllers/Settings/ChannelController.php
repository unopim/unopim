<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Settings\ChannelDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Rules\Code;
use Webkul\Core\Rules\ConvertToArrayIfNeeded;

class ChannelController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected ChannelRepository $channelRepository) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(ChannelDataGrid::class)->toJson();
        }

        return view('admin::settings.channels.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        return view('admin::settings.channels.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        $locales = core()->getAllActiveLocales();

        $rules = [
            'code'              => ['required', 'unique:channels,code', new Code],
            'root_category_id'  => 'required',
            'locales'           => ['required', new ConvertToArrayIfNeeded],
            'currencies'        => ['required', new ConvertToArrayIfNeeded],
        ];

        foreach ($locales as $locale) {
            $rules[$locale->code.'.name'] = 'nullable';
        }

        $this->validate(request(), $rules);

        $data = request()->only(array_keys($rules));

        Event::dispatch('core.channel.create.before');

        $channel = $this->channelRepository->create($data);

        Event::dispatch('core.channel.create.after', $channel);

        session()->flash('success', trans('admin::app.settings.channels.create.create-success'));

        return redirect()->route('admin.settings.channels.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return View
     */
    public function edit(int $id)
    {
        $channel = $this->channelRepository->with(['locales', 'currencies'])->findOrFail($id);

        return view('admin::settings.channels.edit', compact('channel'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(int $id)
    {
        $locales = core()->getAllActiveLocales();

        $rules = [
            'root_category_id'  => 'required',
            'locales'           => ['required', new ConvertToArrayIfNeeded],
            'currencies'        => ['required', new ConvertToArrayIfNeeded],
        ];

        foreach ($locales as $locale) {
            $rules[$locale->code.'.name'] = 'nullable';
        }

        $this->validate(request(), $rules);

        $data = request()->only(array_keys($rules));

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
            ], 400);
        }

        if ($channel->code == config('app.channel')) {
            return new JsonResponse([
                'message'    => trans('admin::app.settings.channels.index.last-delete-error'),
                'message'    => trans('admin::app.settings.channels.index.last-delete-error'),
            ], 400);
        }

        try {
            Event::dispatch('core.channel.delete.before', $id);

            $this->channelRepository->delete($id);

            Event::dispatch('core.channel.delete.after', $id);

            return new JsonResponse([
                'message' => trans('admin::app.settings.channels.index.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.channels.index.delete-failed'),
            ], 500);
        }
    }

    /**
     * Unset keys.
     *
     * @param  array  $keys
     * @return array
     */
    private function unsetKeys($data, $keys)
    {
        foreach ($keys as $key) {
            unset($data[$key]);
        }

        return $data;
    }
}

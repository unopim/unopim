<?php

namespace Webkul\AdminApi\Http\Controllers\API\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;
use Webkul\AdminApi\ApiDataSource\ChannelDataSource;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\AdminApi\Http\Requests\Settings\StoreChannelRequest;
use Webkul\AdminApi\Http\Requests\Settings\UpdateChannelRequest;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;

class ChannelController extends ApiController
{
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CategoryRepository $categoryRepository,
        protected LocaleRepository $localeRepository,
        protected CurrencyRepository $currencyRepository,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            return app(ChannelDataSource::class)->toJson();
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Display a single result of the resource.
     */
    public function get($code): JsonResponse
    {
        try {
            return response()->json(app(ChannelDataSource::class)->getByCode($code));
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Store a newly created channel.
     */
    public function store(StoreChannelRequest $request): JsonResponse
    {
        $data = $this->buildData($request->validated());
        $data['code'] = $request->input('code');

        try {
            Event::dispatch('core.channel.create.before');
            $channel = $this->channelRepository->create($data);
            Event::dispatch('core.channel.create.after', $channel);

            return $this->successResponse(
                trans('admin::app.settings.channels.create.create-success'),
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Update the specified channel, identified by code.
     */
    public function update(UpdateChannelRequest $request, string $code): JsonResponse
    {
        $channel = $this->channelRepository->findOneByField('code', $code);
        if (! $channel) {
            return $this->modelNotFoundResponse(trans('admin::app.settings.channels.index.not-found', ['code' => $code]));
        }

        $data = $this->buildData($request->validated());

        try {
            Event::dispatch('core.channel.update.before', $channel->id);
            $channel = $this->channelRepository->update($data, $channel->id);
            Event::dispatch('core.channel.update.after', $channel);

            return $this->successResponse(trans('admin::app.settings.channels.edit.update-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Remove the specified channel, identified by code.
     */
    public function delete(string $code): JsonResponse
    {
        $channel = $this->channelRepository->findOneByField('code', $code);
        if (! $channel) {
            return $this->modelNotFoundResponse(trans('admin::app.settings.channels.index.not-found', ['code' => $code]));
        }

        if ($this->channelRepository->count() <= 1) {
            return $this->validateErrorResponse(
                ['code' => [trans('admin::app.settings.channels.index.can-not-delete-error', ['channel' => $channel->code])]],
                trans('admin::app.settings.channels.index.can-not-delete-error', ['channel' => $channel->code])
            );
        }

        if ($channel->code == config('app.channel')) {
            return $this->validateErrorResponse(
                ['code' => [trans('admin::app.settings.channels.index.last-delete-error')]],
                trans('admin::app.settings.channels.index.last-delete-error')
            );
        }

        try {
            Event::dispatch('core.channel.delete.before', $channel->id);
            $this->channelRepository->delete($channel->id);
            Event::dispatch('core.channel.delete.after', $channel->id);

            return $this->successResponse(trans('admin::app.settings.channels.index.delete-success'));
        } catch (\Exception $e) {
            return $this->storeExceptionLog($e);
        }
    }

    /**
     * Translate the code-based API payload into the id-based structure the
     * channel repository expects (root_category_id, locale/currency ids, and
     * per-locale name translations).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildData(array $validated): array
    {
        $data = [];

        if (isset($validated['root_category'])) {
            $data['root_category_id'] = $this->categoryRepository->findOneByField('code', $validated['root_category'])?->id;
        }

        if (isset($validated['locales'])) {
            $data['locales'] = $this->localeRepository->whereIn('code', $validated['locales'])->pluck('id')->all();
        }

        if (isset($validated['currencies'])) {
            $data['currencies'] = $this->currencyRepository->whereIn('code', $validated['currencies'])->pluck('id')->all();
        }

        foreach ($validated['labels'] ?? [] as $localeCode => $name) {
            $data[$localeCode]['name'] = $name;
        }

        return $data;
    }
}

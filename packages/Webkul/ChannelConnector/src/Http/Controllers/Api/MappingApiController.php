<?php

namespace Webkul\ChannelConnector\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\ChannelConnector\Http\Requests\MappingRequest;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;
use Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository;
use Webkul\ChannelConnector\Services\MappingService;

class MappingApiController extends Controller
{
    public function __construct(
        protected ChannelConnectorRepository $connectorRepository,
        protected ChannelFieldMappingRepository $mappingRepository,
        protected MappingService $mappingService,
    ) {}

    public function index(string $code): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        $mappings = $this->mappingRepository->findWhere(['channel_connector_id' => $connector->id]);

        return response()->json(['data' => $mappings->map(fn ($m) => [
            'id'                    => $m->id,
            'unopim_attribute_code' => $m->unopim_attribute_code,
            'channel_field'         => $m->channel_field,
            'direction'             => $m->direction,
            'transformation'        => $m->transformation,
            'locale_mapping'        => $m->locale_mapping,
        ])]);
    }

    public function store(MappingRequest $request, string $code): JsonResponse
    {
        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            return response()->json(['message' => 'Connector not found.'], 404);
        }

        $data = $request->validated();
        $errors = $this->mappingService->validateMappings($data['mappings']);

        if (! empty($errors)) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $errors], 422);
        }

        $this->mappingService->saveMappings($connector, $data['mappings']);

        return response()->json(['message' => trans('channel_connector::app.mappings.save-success')]);
    }
}

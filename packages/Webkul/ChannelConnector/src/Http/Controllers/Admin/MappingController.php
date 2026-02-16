<?php

namespace Webkul\ChannelConnector\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Webkul\ChannelConnector\Http\Requests\MappingRequest;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;
use Webkul\ChannelConnector\Repositories\ChannelFieldMappingRepository;
use Webkul\ChannelConnector\Services\MappingService;

class MappingController extends Controller
{
    public function __construct(
        protected ChannelConnectorRepository $connectorRepository,
        protected ChannelFieldMappingRepository $mappingRepository,
        protected MappingService $mappingService,
    ) {}

    public function index(string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.mappings.view')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $mappings = $this->mappingRepository->findWhere(['channel_connector_id' => $connector->id]);
        $suggestions = $this->mappingService->getAutoSuggestedMappings($connector);

        return view('channel_connector::admin.mappings.index', compact('connector', 'mappings', 'suggestions'));
    }

    public function store(MappingRequest $request, string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.mappings.edit')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $data = $request->validated();
        $errors = $this->mappingService->validateMappings($data['mappings']);

        if (! empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        $this->mappingService->saveMappings($connector, $data['mappings']);

        session()->flash('success', trans('channel_connector::app.mappings.save-success'));

        return redirect()->route('admin.channel_connector.mappings.index', $code);
    }

    public function preview(string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.mappings.view')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $mappings = $this->mappingRepository->findWhere(['channel_connector_id' => $connector->id]);

        return view('channel_connector::admin.mappings.preview', compact('connector', 'mappings'));
    }
}

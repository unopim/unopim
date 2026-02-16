<?php

namespace Webkul\ChannelConnector\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\ChannelConnector\DataGrids\ConnectorDataGrid;
use Webkul\ChannelConnector\Events\ConnectorCreated;
use Webkul\ChannelConnector\Events\ConnectorCreating;
use Webkul\ChannelConnector\Events\ConnectorDeleted;
use Webkul\ChannelConnector\Events\ConnectorDeleting;
use Webkul\ChannelConnector\Events\ConnectorUpdated;
use Webkul\ChannelConnector\Events\ConnectorUpdating;
use Webkul\ChannelConnector\Http\Requests\ConnectorRequest;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;

class ConnectorController extends Controller
{
    public function __construct(
        protected ChannelConnectorRepository $connectorRepository,
    ) {}

    public function index()
    {
        if (! bouncer()->hasPermission('channel_connector.connectors.view')) {
            abort(401, 'This action is unauthorized.');
        }

        if (request()->ajax()) {
            return app(ConnectorDataGrid::class)->toJson();
        }

        return view('channel_connector::admin.connectors.index');
    }

    public function create()
    {
        if (! bouncer()->hasPermission('channel_connector.connectors.create')) {
            abort(401, 'This action is unauthorized.');
        }

        return view('channel_connector::admin.connectors.create');
    }

    public function store(ConnectorRequest $request)
    {
        if (! bouncer()->hasPermission('channel_connector.connectors.create')) {
            abort(401, 'This action is unauthorized.');
        }

        $data = $request->validated();

        event(new ConnectorCreating($data));

        if (isset($data['credentials']) && is_string($data['credentials'])) {
            $data['credentials'] = json_decode($data['credentials'], true);
        }

        $connector = $this->connectorRepository->create($data);

        event(new ConnectorCreated($connector));

        session()->flash('success', trans('channel_connector::app.connectors.create-success'));

        return redirect()->route('admin.channel_connector.connectors.index');
    }

    public function edit(string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.connectors.edit')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        return view('channel_connector::admin.connectors.edit', compact('connector'));
    }

    public function update(ConnectorRequest $request, string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.connectors.edit')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $data = $request->validated();

        event(new ConnectorUpdating($connector));

        if (isset($data['credentials']) && is_string($data['credentials'])) {
            $data['credentials'] = json_decode($data['credentials'], true);
        }

        $connector = $this->connectorRepository->update($data, $connector->id);

        event(new ConnectorUpdated($connector));

        session()->flash('success', trans('channel_connector::app.connectors.update-success'));

        return redirect()->route('admin.channel_connector.connectors.index');
    }

    public function destroy(string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.connectors.delete')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        event(new ConnectorDeleting($connector));

        $this->connectorRepository->delete($connector->id);

        event(new ConnectorDeleted($connector));

        session()->flash('success', trans('channel_connector::app.connectors.delete-success'));

        return redirect()->route('admin.channel_connector.connectors.index');
    }

    public function webhooks(string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.webhooks.view')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        return view('channel_connector::admin.webhooks.index', compact('connector'));
    }

    public function manageWebhooks(Request $request, string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.webhooks.manage')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $request->validate([
            'events'           => ['nullable', 'array'],
            'events.*'         => ['string', 'in:product.created,product.updated,product.deleted'],
            'inbound_strategy' => ['nullable', 'string', 'in:auto_update,flag_for_review,ignore'],
        ]);

        $settings = $connector->settings ?? [];

        // Generate webhook token if not present
        if (empty($settings['webhook_token'])) {
            $settings['webhook_token'] = \Illuminate\Support\Str::uuid()->toString();
        }

        $settings['webhook_events'] = $request->input('events', []);

        if ($request->has('inbound_strategy')) {
            $settings['inbound_strategy'] = $request->input('inbound_strategy');
        }

        // Register webhooks with channel via adapter
        $adapter = app(\Webkul\ChannelConnector\Services\AdapterResolver::class)->resolve($connector);
        $callbackUrl = route('channel_connector.webhooks.receive', $settings['webhook_token']);

        try {
            $adapter->registerWebhooks($settings['webhook_events'], $callbackUrl);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['webhooks' => $e->getMessage()]);
        }

        $this->connectorRepository->update(['settings' => $settings], $connector->id);

        session()->flash('success', trans('channel_connector::app.webhooks.manage-success'));

        return redirect()->route('admin.channel_connector.webhooks.index', $code);
    }
}

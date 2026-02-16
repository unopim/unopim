<?php

namespace Webkul\ChannelConnector\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Webkul\ChannelConnector\Repositories\ChannelConnectorRepository;

class SallaOAuthController extends Controller
{
    public function __construct(
        protected ChannelConnectorRepository $connectorRepository,
    ) {}

    public function redirect(string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.connectors.edit')) {
            abort(401, 'This action is unauthorized.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $credentials = $connector->credentials ?? [];
        $clientId = $credentials['client_id'] ?? '';

        $redirectUri = route('admin.channel_connector.salla.callback', $code);

        $state = \Illuminate\Support\Str::random(40);
        session()->put("channel_connector.salla_state.{$code}", $state);

        $params = http_build_query([
            'client_id'     => $clientId,
            'response_type' => 'code',
            'redirect_uri'  => $redirectUri,
            'scope'         => 'offline_access',
            'state'         => $state,
        ]);

        return redirect("https://accounts.salla.sa/oauth2/auth?{$params}");
    }

    public function callback(Request $request, string $code)
    {
        if (! bouncer()->hasPermission('channel_connector.connectors.edit')) {
            abort(401, 'This action is unauthorized.');
        }

        $expectedState = session()->pull("channel_connector.salla_state.{$code}");
        if (! $expectedState || $request->get('state') !== $expectedState) {
            abort(403, 'Invalid OAuth state.');
        }

        $connector = $this->connectorRepository->findOneByField('code', $code);

        if (! $connector) {
            abort(404);
        }

        $authCode = $request->get('code');

        if (! $authCode) {
            session()->flash('error', trans('channel_connector::app.errors.CHN-004'));

            return redirect()->route('admin.channel_connector.connectors.edit', $code);
        }

        $credentials = $connector->credentials ?? [];
        $redirectUri = route('admin.channel_connector.salla.callback', $code);

        try {
            $response = Http::asForm()->post('https://accounts.salla.sa/oauth2/token', [
                'grant_type'    => 'authorization_code',
                'code'          => $authCode,
                'client_id'     => $credentials['client_id'] ?? '',
                'client_secret' => $credentials['client_secret'] ?? '',
                'redirect_uri'  => $redirectUri,
            ]);

            if ($response->failed()) {
                session()->flash('error', trans('channel_connector::app.errors.CHN-004'));

                return redirect()->route('admin.channel_connector.connectors.edit', $code);
            }

            $tokenData = $response->json();

            $credentials['access_token'] = $tokenData['access_token'];
            $credentials['refresh_token'] = $tokenData['refresh_token'] ?? '';
            $credentials['expires_at'] = now()->addSeconds($tokenData['expires_in'] ?? 3600)->toIso8601String();

            $this->connectorRepository->update([
                'credentials' => $credentials,
                'status'      => 'connected',
            ], $connector->id);

            session()->flash('success', trans('channel_connector::app.connectors.test-success'));
        } catch (\Exception $e) {
            session()->flash('error', trans('channel_connector::app.errors.CHN-004'));
        }

        return redirect()->route('admin.channel_connector.connectors.edit', $code);
    }
}

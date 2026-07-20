<?php

namespace Webkul\AdminApi\Http\Controllers\Integrations;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Token;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\AdminApi\DataGrids\Integrations\ApiKeysDataGrid;
use Webkul\AdminApi\Http\Requests\Integrations\GenerateKeyRequest;
use Webkul\AdminApi\Http\Requests\Integrations\RegenerateSecretKeyRequest;
use Webkul\AdminApi\Http\Requests\Integrations\StoreApiKeyRequest;
use Webkul\AdminApi\Http\Requests\Integrations\UpdateApiKeyRequest;
use Webkul\AdminApi\Repositories\ApiKeyRepository;
use Webkul\AdminApi\Traits\OauthClientGenerator;

class ApiKeysController extends Controller
{
    use OauthClientGenerator;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ApiKeyRepository $apiKeyRepository,
        protected ClientRepository $clients
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(ApiKeysDataGrid::class)->toJson();
        }

        return view('admin_api::integrations.api-keys.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $permissionTypes = json_encode($this->apiKeyRepository->getPermissionTypes());

        return view('admin_api::integrations.api-keys.create', compact('permissionTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreApiKeyRequest $request): RedirectResponse
    {
        if (! bouncer()->hasPermission('configuration.integrations.create')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        Event::dispatch('user.api_integration.create.before');

        $data = $request->only([
            'name',
            'permission_type',
            'permissions',
        ]);

        $apiKey = $this->apiKeyRepository->create($data);

        Event::dispatch('user.api_integration.create.after', $apiKey);

        session()->flash('success', trans('admin::app.configuration.integrations.create-success'));

        return redirect()->route('admin.configuration.integrations.edit', $apiKey->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $apiKey = $this->apiKeyRepository->findOrFail($id);

        return view('admin_api::integrations.api-keys.edit', $this->getDefaultDetails($apiKey));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateApiKeyRequest $request, int $id): RedirectResponse
    {
        if (! bouncer()->hasPermission('configuration.integrations.edit')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        $data = array_merge($request->only([
            'name',
            'permission_type',
        ]), [
            'permissions' => $request->has('permissions') ? $request->input('permissions') : [],
        ]);

        Event::dispatch('user.api_integration.update.before', $id);

        $apiKey = $this->apiKeyRepository->update($data, $id);

        Event::dispatch('user.api_integration.update.after', $apiKey);

        session()->flash('success', trans('admin::app.configuration.integrations.update-success'));

        return redirect()->route('admin.configuration.integrations.edit', $id);
    }

    /**
     * Prepares default details for the API key edit view.
     *
     * @param  object  $apiKey  The API key object to retrieve details from.
     * @return array An associative array containing the necessary details for the edit view.
     */
    private function getDefaultDetails($apiKey)
    {
        $oauthClientId = $apiKey->oauthClients?->getKey();
        $clientId = $apiKey->oauthClients?->getKey();
        $secretKey = $oauthClientId ? $this->maskClientIdAndScreatKey($apiKey->oauthClients?->secret) : $apiKey->oauthClients?->secret;

        return [
            'apiKey'          => $apiKey,
            'oauth_client_id' => $oauthClientId,
            'client_id'       => $clientId,
            'secret_key'      => $secretKey,
            'permissionTypes' => json_encode($this->apiKeyRepository->getPermissionTypes()),
        ];
    }

    /**
     * Generates a new client ID and secret key for the API key.
     *
     * This function is responsible for creating a new OAuth client ID and secret key
     * for the specified admin user and API key. The client ID and secret key are then
     * associated with the API key in the database.
     *
     * @return JsonResponse The JSON response containing the new client ID and secret key.
     */
    public function generateKey(GenerateKeyRequest $request): JsonResponse
    {
        if (! bouncer()->hasPermission('configuration.integrations.edit')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        $apiKey = $this->apiKeyRepository->findOrFail($request->input('apiId'));

        $client = $this->generateClientIdAndSecretKey($apiKey->admin_id, $apiKey->name);

        $clientId = $client->getKey();

        $apiKey = $this->apiKeyRepository->update([
            'oauth_client_id' => $clientId,
        ], $apiKey->id);

        return new JsonResponse([
            'client_id'       => $clientId,
            'secret_key'      => $client->plainSecret,
            'oauth_client_id' => $clientId,
        ]);
    }

    /**
     * Regenerates the secret key for the specified OAuth client.
     *
     * This function retrieves the OAuth client ID from the request parameters,
     * finds the corresponding client in the database, and then regenerates its secret key.
     * If the client is not found, it returns a JSON response with a 404 status code and an error message.
     *
     * @return JsonResponse The JSON response containing the regenerated secret key.
     */
    public function regenerateSecretKey(RegenerateSecretKeyRequest $request): JsonResponse
    {
        if (! bouncer()->hasPermission('configuration.integrations.edit')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        $data = $request->validated();

        $client = $this->clients->find($data['oauth_client_id']);

        if (! $client) {
            return new JsonResponse(['message' => trans('admin::app.integrations.api-keys.client-not-found')], JsonResponse::HTTP_NOT_FOUND);
        }

        $client = $this->regenerateSecret($client);

        // Invalidate any access tokens issued against the previous secret so old clients
        // stop working immediately after a regenerate (security best practice).
        Token::where('client_id', $client->getKey())->update(['revoked' => true]);

        return new JsonResponse([
            'secret_key' => $client->plainSecret,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        if (! bouncer()->hasPermission('configuration.integrations.delete')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }

        $apiKey = $this->apiKeyRepository->findOrFail($id);

        try {
            Event::dispatch('user.api_key.delete.before', $id);

            if ($apiKey?->oauthClients) {
                $this->clients->delete($apiKey->oauthClients);
            }

            $this->apiKeyRepository->update(['revoked' => true], $id);

            Event::dispatch('user.api_key.delete.after', $id);

            return new JsonResponse(['message' => trans('admin::app.configuration.integrations.delete-success')]);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans(
                'admin::app.configuration.integrations.delete-failed'
            ),
        ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}

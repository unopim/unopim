<?php

namespace Webkul\AdminApi\Http\Controllers\Integrations;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rule;
use Laravel\Passport\ClientRepository;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\AdminApi\DataGrids\Integrations\ApiKeysDataGrid;
use Webkul\AdminApi\Repositories\ApiKeyRepository;
use Webkul\AdminApi\Traits\OauthClientGenerator;
use Webkul\User\Repositories\AdminRepository;

class ApiKeysController extends Controller
{
    use OauthClientGenerator;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected AdminRepository $adminRepository,
        protected ApiKeyRepository $apiKeyRepository,
        protected ClientRepository $clients
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(ApiKeysDataGrid::class)->toJson();
        }

        return view('admin_api::integrations.api-keys.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $adminUsers = json_encode($this->adminRepository->all(['id', 'name', 'email'])->toArray());

        $permissionTypes = json_encode($this->apiKeyRepository->getPermissionTypes());

        return view('admin_api::integrations.api-keys.create', compact(
            'adminUsers',
            'permissionTypes',
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $this->validate(request(), [
            'name'            => 'required',
            'admin_id'        => ['required', Rule::unique('api_keys')->where(fn (Builder $query) => $query->where('revoked', 0))],
            'permission_type' => 'required',
        ]);

        Event::dispatch('user.api_integration.create.before');

        $data = request()->only([
            'name',
            'admin_id',
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
     *
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $apiKey = $this->apiKeyRepository->findOrFail($id);

        return view('admin_api::integrations.api-keys.edit', $this->getDefaultDetails($apiKey));
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Validation\ValidationException If the required parameters are not provided.
     */
    public function update(int $id)
    {
        $this->validate(request(), [
            'name'            => 'required',
            'permission_type' => 'required|in:all,custom',
        ]);

        $data = array_merge(request()->only([
            'name',
            'permission_type',
        ]), [
            'permissions' => request()->has('permissions') ? request('permissions') : [],
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
     * @return \Illuminate\Http\JsonResponse The JSON response containing the new client ID and secret key.
     *
     * @throws \Illuminate\Validation\ValidationException If the required parameters are not provided.
     */
    public function generateKey()
    {
        $this->validate(request(), [
            'name'     => 'required',
            'admin_id' => 'required',
            'apiId'    => 'required',
        ]);

        $data = request()->only([
            'name',
            'admin_id',
            'apiId',
        ]);

        $userId = $data['admin_id'];
        $name = $data['name'];

        $client = $this->generateClientIdAndSecretKey($userId, $name);

        $id = $name = $data['apiId'];

        $clientId = null;
        $clientId = $client->getKey();

        $apiKey = $this->apiKeyRepository->update([
            'oauth_client_id' => $clientId,
        ], $id);

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
     * @return \Illuminate\Http\JsonResponse The JSON response containing the regenerated secret key.
     */
    public function regenerateSecretKey()
    {
        $data = request()->only('oauth_client_id');

        $client = $this->clients->find($data['oauth_client_id']);

        if (! $client) {
            return new JsonResponse(['message' => trans('admin::app.integrations.api-keys.client-not-found')], 404);
        }

        $client = $this->regenerateSecret($client);

        return new JsonResponse([
            'secret_key' => $client->plainSecret,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
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
        }

        return new JsonResponse([
            'message' => trans(
                'admin::app.configuration.integrations.delete-failed'
            ),
        ], 500);
    }
}

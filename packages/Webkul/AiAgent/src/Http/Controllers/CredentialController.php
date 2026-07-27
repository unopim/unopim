<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\AiAgent\DataGrids\Credential\CredentialDataGrid;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Http\Requests\CredentialForm;
use Webkul\AiAgent\Repositories\CredentialRepository;

class CredentialController extends Controller
{
    public function __construct(
        protected CredentialRepository $credentialRepository,
        protected AiApiClient $apiClient,
    ) {
        $this->middleware(function ($request, $next) {
            abort_unless(bouncer()->hasPermission('ai-agent.credentials'), 403, trans('ai-agent::app.common.unauthorized'));

            return $next($request);
        });
    }

    /**
     * Display a listing of credentials.
     *
     * @return View|JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return resolve(CredentialDataGrid::class)->toJson();
        }

        return view('ai-agent::credentials.index');
    }

    /**
     * Show the form for creating a new credential.
     *
     * @return View
     */
    public function create(): Factory|\Illuminate\Contracts\View\View
    {
        return view('ai-agent::credentials.create');
    }

    /**
     * Store a newly created credential.
     */
    public function store(CredentialForm $request): JsonResponse
    {
        $this->credentialRepository->create($request->validated());

        return new JsonResponse([
            'redirect_url' => route('ai-agent.credentials.index'),
            'message'      => trans('ai-agent::app.credentials.create-success'),
        ]);
    }

    /**
     * Show the form for editing a credential.
     *
     * @return View
     */
    public function edit(int $id): Factory|\Illuminate\Contracts\View\View
    {
        $credential = $this->credentialRepository->findOrFail($id);

        return view('ai-agent::credentials.edit', ['credential' => $credential]);
    }

    /**
     * Update the specified credential.
     */
    public function update(CredentialForm $request, int $id): JsonResponse
    {
        $this->credentialRepository->update($this->resolveApiKey($request->validated(), $id), $id);

        return new JsonResponse([
            'redirect_url' => route('ai-agent.credentials.index'),
            'message'      => trans('ai-agent::app.credentials.update-success'),
        ]);
    }

    /**
     * Keep the stored key when the submitted value is the masked placeholder, so
     * editing a credential without retyping the key never overwrites or clears it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveApiKey(array $data, ?int $id): array
    {
        $apiKey = $data['apiKey'] ?? null;

        if ($apiKey && preg_match('/^\*+$/', $apiKey)) {
            $original = $id ? $this->credentialRepository->find($id)?->apiKey : null;

            if ($original) {
                $data['apiKey'] = $original;
            } else {
                unset($data['apiKey']);
            }
        }

        return $data;
    }

    /**
     * Remove the specified credential.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->credentialRepository->delete($id);

        return new JsonResponse([
            'redirect_url' => route('ai-agent.credentials.index'),
            'message'      => trans('ai-agent::app.credentials.delete-success'),
        ]);
    }

    /**
     * Get active credentials list for async dropdowns.
     */
    public function get(): JsonResponse
    {
        return new JsonResponse($this->credentialRepository->getActiveList());
    }

    /**
     * Test connectivity with the AI provider.
     */
    public function testConnection(CredentialForm $request): JsonResponse
    {
        $data = $this->resolveApiKey($request->validated(), $request->integer('id') ?: null);
        $config = CredentialConfig::fromModel($data);

        $this->apiClient->configure($config);
        $result = $this->apiClient->testConnection();

        return new JsonResponse([
            'success' => $result['success'],
            'message' => $result['success']
                ? trans('ai-agent::app.credentials.test-success')
                : trans('ai-agent::app.credentials.test-failed'),
        ]);
    }
}

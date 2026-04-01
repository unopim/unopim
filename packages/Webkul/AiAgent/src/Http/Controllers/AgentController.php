<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\AiAgent\DataGrids\Agent\AgentDataGrid;
use Webkul\AiAgent\Http\Requests\AgentForm;
use Webkul\AiAgent\Repositories\AgentRepository;
use Webkul\AiAgent\Repositories\CredentialRepository;

class AgentController extends Controller
{
    public function __construct(
        protected AgentRepository $agentRepository,
        protected CredentialRepository $credentialRepository,
    ) {
        $this->middleware(function ($request, $next) {
            if (! bouncer()->hasPermission('ai-agent.agents')) {
                abort(401, trans('ai-agent::app.common.unauthorized'));
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of agents.
     *
     * @return View|JsonResponse
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(AgentDataGrid::class)->toJson();
        }

        return view('ai-agent::agents.index');
    }

    /**
     * Show the form for creating a new agent.
     *
     * @return View
     */
    public function create()
    {
        $credentials = $this->credentialRepository->getActiveList();

        return view('ai-agent::agents.create', compact('credentials'));
    }

    /**
     * Store a newly created agent.
     */
    public function store(AgentForm $request): JsonResponse
    {
        $this->agentRepository->create($request->validated());

        return new JsonResponse([
            'redirect_url' => route('ai-agent.agents.index'),
            'message'      => trans('ai-agent::app.agents.create-success'),
        ]);
    }

    /**
     * Show the form for editing an agent.
     *
     * @return View
     */
    public function edit(int $id)
    {
        $agent = $this->agentRepository->findOrFail($id);
        $credentials = $this->credentialRepository->getActiveList();

        return view('ai-agent::agents.edit', compact('agent', 'credentials'));
    }

    /**
     * Update the specified agent.
     */
    public function update(AgentForm $request, int $id): JsonResponse
    {
        $this->agentRepository->update($request->validated(), $id);

        return new JsonResponse([
            'redirect_url' => route('ai-agent.agents.index'),
            'message'      => trans('ai-agent::app.agents.update-success'),
        ]);
    }

    /**
     * Remove the specified agent.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->agentRepository->delete($id);

        return new JsonResponse([
            'redirect_url' => route('ai-agent.agents.index'),
            'message'      => trans('ai-agent::app.agents.delete-success'),
        ]);
    }

    /**
     * Get active agents list for async dropdowns.
     */
    public function get(): JsonResponse
    {
        return new JsonResponse($this->agentRepository->getActiveList());
    }
}

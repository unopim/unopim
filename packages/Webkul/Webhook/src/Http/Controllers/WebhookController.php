<?php

namespace Webkul\Webhook\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Webhook\DataGrids\WebhookDataGrid;
use Webkul\Webhook\Http\Requests\WebhookForm;
use Webkul\Webhook\Registry\EventRegistry;
use Webkul\Webhook\Repositories\WebhookRepository;
use Webkul\Webhook\Validators\SafeWebhookUrl;

class WebhookController
{
    protected const TEST_REQUEST_TIMEOUT = 10;

    public function __construct(
        protected WebhookRepository $webhookRepository
    ) {}

    /**
     * Display a listing of the webhooks.
     */
    public function index(): View|JsonResponse
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook'), 403);

        if (request()->ajax()) {
            return resolve(WebhookDataGrid::class)->toJson();
        }

        return view('webhook::webhooks.index');
    }

    /**
     * Show the form for creating a new webhook.
     */
    public function create(): View
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.create'), 403);

        return view('webhook::webhooks.create', [
            'eventGroups' => $this->eventGroups(),
        ]);
    }

    /**
     * Store a newly created webhook.
     */
    public function store(WebhookForm $request): RedirectResponse
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.create'), 403);

        $webhook = $this->webhookRepository->create($this->normalize($request));

        session()->flash('success', trans('webhook::app.webhooks.create-success'));

        return to_route('webhook.edit', $webhook->id);
    }

    /**
     * Show the form for editing the specified webhook.
     */
    public function edit(int $id): View
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.edit'), 403);

        $webhook = $this->webhookRepository->findOrFail($id);

        return view('webhook::webhooks.edit', [
            'webhook'     => $webhook,
            'eventGroups' => $this->eventGroups(),
        ]);
    }

    /**
     * Update the specified webhook.
     */
    public function update(WebhookForm $request, int $id): RedirectResponse
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.edit'), 403);

        $data = $this->normalize($request);

        if (! $request->filled('secret')) {
            unset($data['secret']);
        }

        $this->webhookRepository->update($data, $id);

        session()->flash('success', trans('webhook::app.webhooks.update-success'));

        return to_route('webhook.edit', $id);
    }

    /**
     * Remove the specified webhook.
     */
    public function destroy(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.delete'), 403);

        try {
            $this->webhookRepository->delete($id);

            return new JsonResponse([
                'message' => trans('webhook::app.webhooks.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse([
                'message' => trans('webhook::app.webhooks.delete-failed'),
            ], 500);
        }
    }

    /**
     * Mass delete webhooks.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.delete'), 403);

        foreach ($massDestroyRequest->input('indices', []) as $id) {
            if ($this->webhookRepository->find($id)) {
                $this->webhookRepository->delete($id);
            }
        }

        return new JsonResponse([
            'message' => trans('webhook::app.webhooks.delete-success'),
        ]);
    }

    /**
     * Probe a webhook URL with a test payload before the admin saves it.
     */
    public function test(Request $request): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('configuration.webhook.create') || bouncer()->hasPermission('configuration.webhook.edit'), 403);

        $url = (string) $request->input('url');

        if (! SafeWebhookUrl::validate($url)['valid']) {
            return new JsonResponse([
                'success' => false,
                'message' => trans('webhook::app.webhooks.validation.unsafe-url'),
            ], 422);
        }

        try {
            $response = Http::withOptions(SafeWebhookUrl::httpOptions($url))
                ->timeout(self::TEST_REQUEST_TIMEOUT)
                ->post($url, [
                    'event'     => 'webhook.test',
                    'timestamp' => now()->toDateTimeString(),
                    'message'   => trans('webhook::app.webhooks.test.payload-message'),
                ]);
        } catch (\Throwable) {
            return new JsonResponse([
                'success' => false,
                'message' => trans('webhook::app.webhooks.test.connection-failed'),
            ], 422);
        }

        if (! $response->successful()) {
            return new JsonResponse([
                'success' => false,
                'message' => trans('webhook::app.webhooks.test.unreachable', ['code' => $response->status()]),
            ], 422);
        }

        return new JsonResponse([
            'success' => true,
            'message' => trans('webhook::app.webhooks.test.reachable'),
        ]);
    }

    /**
     * Build the validated attribute set for persistence.
     *
     * @return array<string, mixed>
     */
    protected function normalize(WebhookForm $request): array
    {
        return [
            'name'      => $request->input('name'),
            'url'       => $request->input('url'),
            'is_active' => $request->boolean('is_active'),
            'events'    => array_values((array) $request->input('events', [])),
            'secret'    => $request->input('secret'),
            'headers'   => $this->normalizeHeaders($request->input('headers', [])),
        ];
    }

    /**
     * Reduce the submitted header rows to a non-empty key/value map.
     *
     * @return array<string, string>
     */
    protected function normalizeHeaders(mixed $headers): array
    {
        $normalized = [];

        foreach ((array) $headers as $row) {
            $key = trim((string) ($row['key'] ?? ''));

            if ($key !== '') {
                $normalized[$key] = (string) ($row['value'] ?? '');
            }
        }

        return $normalized;
    }

    /**
     * The subscribable event catalog shaped for the form multiselect.
     *
     * @return array<int, array{entity: string, options: array<int, array{id: string, label: string}>}>
     */
    protected function eventGroups(): array
    {
        return resolve(EventRegistry::class)->forSelect();
    }
}

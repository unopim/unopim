<?php

namespace Webkul\ProductPassport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\ProductPassport\DataGrids\Catalog\PassportTemplateDataGrid;
use Webkul\ProductPassport\Http\Requests\PassportTemplateRequest;
use Webkul\ProductPassport\Models\PassportTemplateFamilyProxy;
use Webkul\ProductPassport\Repositories\PassportTemplateRepository;

class PassportTemplateController extends Controller
{
    public function __construct(
        protected PassportTemplateRepository $templateRepository,
        protected LocaleRepository $localeRepository,
    ) {}

    public function index(): View|JsonResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.template.view'), 403);

        abort_unless(PublicationController::featureEnabled(), 404);

        if (request()->ajax()) {
            return app(PassportTemplateDataGrid::class)->toJson();
        }

        return view('passport::admin.templates.index');
    }

    public function store(PassportTemplateRequest $request): RedirectResponse
    {
        abort_unless(PublicationController::featureEnabled(), 404);

        Event::dispatch('catalog.passport_template.create.before');

        $template = $this->templateRepository->create($this->payload($request));

        Event::dispatch('catalog.passport_template.create.after', $template);

        session()->flash('success', trans('passport::app.templates.create-success'));

        return redirect()->route('admin.catalog.passports.templates.edit', $template->id);
    }

    public function edit(int $id): View
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.template.edit'), 403);

        abort_unless(PublicationController::featureEnabled(), 404);

        $template = $this->templateRepository->getModel()->newQuery()
            ->with([
                'translations',
                'families',
                'sections.translations',
                'fields.translations',
                'fields.attribute.translations',
                'fields.section',
            ])
            ->findOrFail($id);

        return view('passport::admin.templates.edit', [
            'template'        => $template,
            'locales'         => $this->localeRepository->getActiveLocales(),
            'claimedFamilies' => $this->claimedFamilies($template->id),
        ]);
    }

    public function update(PassportTemplateRequest $request, int $id): JsonResponse
    {
        abort_unless(PublicationController::featureEnabled(), 404);

        Event::dispatch('catalog.passport_template.update.before', $id);

        $template = $this->templateRepository->update($this->payload($request), $id);

        Event::dispatch('catalog.passport_template.update.after', $template);

        return new JsonResponse([
            'message'      => trans('passport::app.templates.update-success'),
            'redirect_url' => route('admin.catalog.passports.templates.edit', $template->id),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.template.delete'), 403);

        abort_unless(PublicationController::featureEnabled(), 404);

        $this->templateRepository->findOrFail($id);

        Event::dispatch('catalog.passport_template.delete.before', $id);

        $this->templateRepository->delete($id);

        Event::dispatch('catalog.passport_template.delete.after', $id);

        return new JsonResponse(['message' => trans('passport::app.templates.delete-success')]);
    }

    /**
     * Families another template already owns. The picker excludes them, so the
     * one-template-per-family rule is visible while choosing rather than only
     * after a refused save.
     *
     * @return list<int>
     */
    private function claimedFamilies(int $templateId): array
    {
        return PassportTemplateFamilyProxy::modelClass()::query()
            ->where('passport_template_id', '!=', $templateId)
            ->pluck('attribute_family_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * The code is immutable once created: it identifies the template in exported
     * payloads, so an edit submits it read-only and it is dropped here.
     *
     * @return array<string, mixed>
     */
    private function payload(PassportTemplateRequest $request): array
    {
        $data = $request->validated();

        $data['is_enabled'] = $request->boolean('is_enabled');

        if ($request->route('id') !== null) {
            unset($data['code']);
        }

        return $data;
    }
}

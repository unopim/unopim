<?php

namespace Webkul\ProductPassport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\ProductPassport\Http\Requests\UpdatePassportMappingRequest;

class PassportMappingController extends Controller
{
    /**
     * Passport fields are the seeded `dpp_*` attributes; every other attribute
     * is eligible as a source. Matched by code prefix so the screen is
     * family-independent — a merchant maps a field once, for every family.
     */
    private const DPP_CODE_PREFIX = 'dpp_';

    private const MAPPING_PREFIX = 'catalog.product_passport.mapping.';

    public function __construct(
        protected CoreConfigRepository $coreConfigRepository,
    ) {}

    public function edit(): View
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.mapping'), 403);

        abort_unless(PublicationController::featureEnabled(), 404);

        $passportFields = $this->passportFields();

        $sourceOptions = $this->sourceOptions();

        $mapping = $passportFields->mapWithKeys(fn ($attribute): array => [
            $attribute->code => (string) (core()->getConfigData(self::MAPPING_PREFIX.$attribute->code) ?? ''),
        ])->all();

        return view('passport::admin.mapping.index', [
            'passportFields' => $passportFields,
            'sourceOptions'  => $sourceOptions,
            'mapping'        => $mapping,
        ]);
    }

    public function update(UpdatePassportMappingRequest $request): JsonResponse
    {
        abort_unless(PublicationController::featureEnabled(), 404);

        $payload = [];

        foreach ($request->validated('mapping') ?? [] as $field => $source) {
            Arr::set($payload, self::MAPPING_PREFIX.$field, $source ?: null);
        }

        foreach (['channel', 'locale'] as $scope) {
            if ($request->filled($scope)) {
                $payload[$scope] = $request->input($scope);
            }
        }

        if ($payload !== []) {
            $this->coreConfigRepository->create($payload);
        }

        return new JsonResponse([
            'message'      => trans('passport::app.mapping.saved'),
            'redirect_url' => route('admin.catalog.passports.mapping.edit'),
        ]);
    }

    /**
     * @return Collection<int, Attribute>
     */
    private function passportFields(): Collection
    {
        return AttributeProxy::modelClass()::query()
            ->where('code', 'like', self::DPP_CODE_PREFIX.'%')
            ->with('translations')
            ->orderBy('code')
            ->get();
    }

    /**
     * Eligible source attributes shaped for the component select: `id` is the
     * attribute code (what persists and what the builder resolves), `label` the
     * translated attribute name for the current locale.
     *
     * @return list<array{id: string, label: string}>
     */
    private function sourceOptions(): array
    {
        return AttributeProxy::modelClass()::query()
            ->where('code', 'not like', self::DPP_CODE_PREFIX.'%')
            ->with('translations')
            ->orderBy('code')
            ->get()
            ->map(fn ($attribute): array => [
                'id'    => $attribute->code,
                'label' => $attribute->getTranslatedValueWithFallback('name') ?: $attribute->code,
            ])
            ->values()
            ->all();
    }
}

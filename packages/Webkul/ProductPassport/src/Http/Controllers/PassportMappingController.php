<?php

namespace Webkul\ProductPassport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Models\AttributeFamilyGroupMappingProxy;
use Webkul\Attribute\Models\AttributeGroupProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\ProductPassport\Http\Requests\StorePassportFieldRequest;
use Webkul\ProductPassport\Http\Requests\UpdatePassportMappingRequest;

class PassportMappingController extends Controller
{
    /**
     * Passport fields are the members of the `dpp` attribute GROUP — the same
     * single source of truth `PassportPayloadBuilder` publishes from, so a
     * merchant's own attribute added to the group (with any code) is both
     * published and mappable. Membership is read group-wide (across every
     * family), because a mapping is family-independent.
     */
    private const GROUP_CODE = 'dpp';

    /**
     * File/image passport fields carry a document; a text/select field carries
     * a value. A source may only feed a field of its own class, so the screen
     * offers document sources to document fields and value sources to value
     * fields, and the request rejects any cross-class mapping.
     */
    private const DOCUMENT_TYPES = ['file', 'image'];

    private const MAPPING_PREFIX = 'catalog.product_passport.mapping.';

    public function __construct(
        protected CoreConfigRepository $coreConfigRepository,
        protected AttributeRepository $attributeRepository,
    ) {}

    public function edit(): View
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.mapping'), 403);

        abort_unless(PublicationController::featureEnabled(), 404);

        $dppAttributeIds = $this->dppGroupAttributeIds();

        $passportFields = $this->passportFields($dppAttributeIds);

        $sourceAttributes = $this->sourceAttributes($dppAttributeIds);

        $mapping = $this->mappingFor($passportFields);

        $sourceOptions = $passportFields->mapWithKeys(fn ($attribute): array => [
            $attribute->code => $this->compatibleSourceOptions($attribute, $sourceAttributes),
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

        $this->persistMapping(
            $request->validated('mapping') ?? [],
            $request->filled('channel') ? (string) $request->input('channel') : null,
            $request->filled('locale') ? (string) $request->input('locale') : null,
        );

        return new JsonResponse([
            'message'      => trans('passport::app.mapping.saved'),
            'redirect_url' => route('admin.catalog.passports.mapping.edit'),
        ]);
    }

    /**
     * Create a genuine passport field: a real attribute (its code is what the
     * payload and JSON-LD key off) added to the `dpp` group so it becomes both
     * publishable and mappable. Reuses the canonical attribute quick-create path.
     */
    public function storeField(StorePassportFieldRequest $request): JsonResponse
    {
        abort_unless(PublicationController::featureEnabled(), 404);

        $data = $request->all();

        if (($data['validation'] ?? null) === 'none') {
            $data['validation'] = null;
        }

        Event::dispatch('catalog.attribute.create.before');

        $attribute = $this->attributeRepository->create($data);

        Event::dispatch('catalog.attribute.create.after', $attribute);

        $this->attachToDppGroup((int) $attribute->id);

        return new JsonResponse([
            'message'      => trans('passport::app.mapping.field-created'),
            'redirect_url' => route('admin.catalog.passports.mapping.edit'),
        ]);
    }

    /**
     * Attach a newly created field to the `dpp` group in every family that
     * already uses it, so it surfaces on all passport-enabled products at once.
     * Families without the group are untouched; `syncWithoutDetaching` keeps the
     * call idempotent.
     */
    private function attachToDppGroup(int $attributeId): void
    {
        $group = AttributeGroupProxy::modelClass()::query()->where('code', self::GROUP_CODE)->first();

        if ($group === null) {
            return;
        }

        AttributeFamilyGroupMappingProxy::modelClass()::query()
            ->where('attribute_group_id', $group->id)
            ->get()
            ->each(fn ($mapping) => $mapping->customAttributes()->syncWithoutDetaching([$attributeId]));
    }

    /**
     * Persist a passport field->source mapping (and optional channel/locale
     * scope) as core_config rows. Shared by the admin screen and the REST API.
     *
     * @param  array<string, string|null>  $mapping
     */
    public function persistMapping(array $mapping, ?string $channel = null, ?string $locale = null): void
    {
        $payload = [];

        foreach ($mapping as $field => $source) {
            Arr::set($payload, self::MAPPING_PREFIX.$field, $source ?: null);
        }

        if ($channel !== null) {
            $payload['channel'] = $channel;
        }

        if ($locale !== null) {
            $payload['locale'] = $locale;
        }

        if ($payload !== []) {
            $this->coreConfigRepository->create($payload);
        }
    }

    /**
     * Field-code => mapped-source-code map for every dpp passport field. Shared
     * with the REST API so admin and API read the same single source of truth.
     *
     * @return array<string, string>
     */
    public function mappingData(): array
    {
        return $this->mappingFor($this->passportFields($this->dppGroupAttributeIds()));
    }

    /**
     * @param  Collection<int, Attribute>  $passportFields
     * @return array<string, string>
     */
    private function mappingFor(Collection $passportFields): array
    {
        return $passportFields->mapWithKeys(fn ($attribute): array => [
            $attribute->code => (string) (core()->getConfigData(self::MAPPING_PREFIX.$attribute->code) ?? ''),
        ])->all();
    }

    /**
     * IDs of every attribute that belongs to the `dpp` group in any family —
     * the mirror of `PassportPayloadBuilder`'s group-membership leak control,
     * read group-wide because a mapping applies to every family at once.
     *
     * @return list<int>
     */
    private function dppGroupAttributeIds(): array
    {
        $group = AttributeGroupProxy::modelClass()::query()->where('code', self::GROUP_CODE)->first();

        if ($group === null) {
            return [];
        }

        return AttributeProxy::modelClass()::query()
            ->join('attribute_group_mappings', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')
            ->join('attribute_family_group_mappings', 'attribute_group_mappings.attribute_family_group_id', '=', 'attribute_family_group_mappings.id')
            ->where('attribute_family_group_mappings.attribute_group_id', $group->id)
            ->distinct()
            ->pluck('attributes.id')
            ->all();
    }

    /**
     * @param  list<int>  $dppAttributeIds
     * @return Collection<int, Attribute>
     */
    private function passportFields(array $dppAttributeIds): Collection
    {
        return AttributeProxy::modelClass()::query()
            ->whereIn('id', $dppAttributeIds)
            ->with('translations')
            ->orderBy('code')
            ->get();
    }

    /**
     * Every attribute that is NOT itself a `dpp` field is an eligible source.
     * `whereNotIn('id', [])` intentionally matches all rows, so on an install
     * with no family using the group yet, every attribute stays selectable.
     *
     * @param  list<int>  $dppAttributeIds
     * @return Collection<int, Attribute>
     */
    private function sourceAttributes(array $dppAttributeIds): Collection
    {
        return AttributeProxy::modelClass()::query()
            ->whereNotIn('id', $dppAttributeIds)
            ->with('translations')
            ->orderBy('code')
            ->get();
    }

    /**
     * Sources whose document/value class matches the field's, shaped for the
     * component select: `id` is the attribute code (what persists and what the
     * builder resolves), `label` the translated attribute name.
     *
     * @param  Collection<int, Attribute>  $sourceAttributes
     * @return list<array{id: string, label: string}>
     */
    private function compatibleSourceOptions(Attribute $field, Collection $sourceAttributes): array
    {
        $fieldIsDocument = in_array($field->type, self::DOCUMENT_TYPES, true);

        return $sourceAttributes
            ->filter(fn ($attribute): bool => in_array($attribute->type, self::DOCUMENT_TYPES, true) === $fieldIsDocument)
            ->map(fn ($attribute): array => [
                'id'    => $attribute->code,
                'label' => $attribute->getTranslatedValueWithFallback('name') ?: $attribute->code,
            ])
            ->values()
            ->all();
    }
}

<?php

namespace Webkul\ProductPassport\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Models\AttributeGroupProxy;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Core\Repositories\CoreConfigRepository;
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

    /**
     * A single core_config row holding the merchant's own passport fields as a
     * JSON array of `{name, attribute}` objects — user-typed label plus the
     * source attribute code the payload builder resolves the value from.
     */
    private const CUSTOM_FIELDS_KEY = 'catalog.product_passport.custom_fields';

    public function __construct(
        protected CoreConfigRepository $coreConfigRepository,
    ) {}

    public function edit(): View
    {
        abort_unless(bouncer()->hasPermission('catalog.passport.mapping'), 403);

        abort_unless(PublicationController::featureEnabled(), 404);

        $passportFields = $this->passportFields($this->dppGroupAttributeIds());

        $sourceParams = $passportFields->mapWithKeys(fn ($attribute): array => [
            $attribute->code => $this->sourceQueryParams(in_array($attribute->type, self::DOCUMENT_TYPES, true)),
        ])->all();

        return view('passport::admin.mapping.index', [
            'passportFields'     => $passportFields,
            'sourceParams'       => $sourceParams,
            'mapping'            => $this->mappingFor($passportFields),
            'customSourceParams' => $this->sourceQueryParams(false),
            'customFields'       => $this->customFieldRows(),
        ]);
    }

    public function update(UpdatePassportMappingRequest $request): JsonResponse
    {
        abort_unless(PublicationController::featureEnabled(), 404);

        $channel = $request->filled('channel') ? (string) $request->input('channel') : null;
        $locale = $request->filled('locale') ? (string) $request->input('locale') : null;

        $this->persistMapping($request->validated('mapping') ?? [], $channel, $locale);

        $this->persistCustomFields($request->validated('custom_fields') ?? [], $channel, $locale);

        return new JsonResponse([
            'message'      => trans('passport::app.mapping.saved'),
            'redirect_url' => route('admin.catalog.passports.mapping.edit'),
        ]);
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
     * Persist the merchant's custom passport fields as a single JSON core_config
     * row. An empty list writes `[]`, so clearing every row removes the rows
     * from the published payload — an unset key stays fully backward-compatible.
     *
     * @param  list<array{name: string, attribute: string}>  $customFields
     */
    public function persistCustomFields(array $customFields, ?string $channel = null, ?string $locale = null): void
    {
        $rows = array_values(array_map(fn (array $row): array => [
            'name'      => trim((string) $row['name']),
            'attribute' => (string) $row['attribute'],
        ], $customFields));

        $payload = [];

        Arr::set($payload, self::CUSTOM_FIELDS_KEY, json_encode($rows));

        if ($channel !== null) {
            $payload['channel'] = $channel;
        }

        if ($locale !== null) {
            $payload['locale'] = $locale;
        }

        $this->coreConfigRepository->create($payload);
    }

    /**
     * The saved custom fields, decoded for the admin screen to hydrate its
     * reactive row list.
     *
     * @return list<array{name: string, attribute: string}>
     */
    public function customFieldsData(): array
    {
        $raw = core()->getConfigData(self::CUSTOM_FIELDS_KEY);

        $rows = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($row): array => [
            'name'      => trim((string) ($row['name'] ?? '')),
            'attribute' => (string) ($row['attribute'] ?? ''),
        ], $rows), fn (array $row): bool => $row['name'] !== '' && $row['attribute'] !== ''));
    }

    /**
     * Query params the async attribute picker sends on every search, so the
     * source list is filtered and paginated in SQL. Loading the whole attribute
     * table into the page does not scale — a large catalog holds tens of
     * thousands of attributes. A `dpp` field is never its own source, and a
     * document field only accepts a document source (and vice versa).
     *
     * @return array<string, string|list<string>>
     */
    private function sourceQueryParams(bool $documents): array
    {
        return [
            'notInGroup'                      => self::GROUP_CODE,
            $documents ? 'types' : 'notTypes' => self::DOCUMENT_TYPES,
        ];
    }

    /**
     * Saved custom fields plus the label of each source attribute, so the screen
     * can show the current selection without fetching the whole attribute list.
     *
     * @return list<array{name: string, attribute: string, label: string}>
     */
    private function customFieldRows(): array
    {
        $rows = $this->customFieldsData();

        if ($rows === []) {
            return [];
        }

        $labels = AttributeProxy::modelClass()::query()
            ->whereIn('code', array_column($rows, 'attribute'))
            ->with('translations')
            ->get()
            ->mapWithKeys(fn ($attribute): array => [
                $attribute->code => $attribute->getTranslatedValueWithFallback('name') ?: $attribute->code,
            ]);

        return array_map(fn (array $row): array => [
            ...$row,
            'label' => $labels[$row['attribute']] ?? $row['attribute'],
        ], $rows);
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
}

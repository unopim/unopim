<?php

namespace Webkul\Measurement\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Webkul\Admin\Http\Controllers\VueJsSelect\AbstractOptionsController;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;

class MeasurementOptionsController extends AbstractOptionsController
{
    public function __construct(
        protected MeasurementFamilyRepository $measurementFamilyRepository,
        AttributeRepository $attributeRepository,
        LocaleRepository $localeRepository,
        CurrencyRepository $currencyRepository,
        ChannelRepository $channelRepository,
        CategoryFieldRepository $categoryFieldRepository,
        protected AttributeMeasurementRepository $attributeMeasurementRepository
    ) {
        parent::__construct(
            $attributeRepository,
            $localeRepository,
            $currencyRepository,
            $channelRepository,
            $categoryFieldRepository
        );
    }

    /**
     * Get measurement unit options for the given attribute.
     */
    public function getOptions(): JsonResponse
    {
        $attributeId = request('attribute_id');
        $page = request('page', 1);
        $query = request('query', '');
        $queryParams = request('queryParams', []);

        $attributeMeasurement = $this->attributeMeasurementRepository->getByAttributeId($attributeId);

        $familyCode = $attributeMeasurement?->family_code;

        if (! $familyCode) {
            return response()->json([
                'options'  => [],
                'page'     => 1,
                'lastPage' => 1,
            ]);
        }

        $currentLocale = app()->getLocale();
        $currentLang = strtok($currentLocale, '_');

        $this->measurementFamilyRepository->findOneByField('code', $familyCode);
        $defaultUnit = $attributeMeasurement?->unit_code;

        $units = collect(
            $this->measurementFamilyRepository->getUnitsByFamilyCode($familyCode)
        )->map(function ($unit) use ($currentLocale, $currentLang, $defaultUnit) {

            $labels = $unit['labels'] ?? [];

            if (isset($labels[$currentLocale])) {
                $label = $labels[$currentLocale];
            } elseif ($firstLangMatch = collect($labels)->first(fn ($_, $key): bool => str_starts_with($key, $currentLang))) {
                $label = $firstLangMatch;
            } else {
                $label = $unit['code'];
            }

            return (object) [
                'id'         => $unit['code'],
                'label'      => $label,
                'code'       => $unit['code'],
                'is_default' => $unit['code'] === $defaultUnit,
                'attribute'  => [
                    'swatch_type' => null,
                ],
            ];
        });

        $options = $this->formatCollection(
            $units,
            $page,
            50,
            $query,
            $queryParams,
            $defaultUnit
        );

        return response()->json($options);
    }

    /**
     * Format measurement units collection for dropdown response.
     */
    protected function formatCollection(
        Collection $collection,
        int $page,
        int $limit,
        string $query,
        array $queryParams,
        ?string $defaultUnit = null
    ): array {
        $selectedValue = $queryParams['identifiers']['value'] ?? null;

        if (empty($selectedValue) || $selectedValue === '__auto__') {
            $collection = $collection->sortByDesc('is_default');
        } else {
            $collection = $collection->sortByDesc(fn ($item): bool => $item->id === $selectedValue);
        }

        $paginated = $collection->forPage($page, $limit)->values();

        return [
            'options'  => $paginated,
            'page'     => $page,
            'lastPage' => ceil($collection->count() / $limit),
        ];
    }
}

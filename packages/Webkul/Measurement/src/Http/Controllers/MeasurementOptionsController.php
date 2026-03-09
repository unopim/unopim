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
use Webkul\Measurement\Repository\AttributeMeasurementRepository;
use Webkul\Measurement\Repository\MeasurementFamilyRepository;

class MeasurementOptionsController extends AbstractOptionsController
{
    protected MeasurementFamilyRepository $measurementFamilyRepository;

    protected AttributeMeasurementRepository $attributeMeasurementRepository;

    public function __construct(
        MeasurementFamilyRepository $measurementFamilyRepository,
        AttributeRepository $attributeRepository,
        LocaleRepository $localeRepository,
        CurrencyRepository $currencyRepository,
        ChannelRepository $channelRepository,
        CategoryFieldRepository $categoryFieldRepository,
        AttributeMeasurementRepository $attributeMeasurementRepository
    ) {
        parent::__construct(
            $attributeRepository,
            $localeRepository,
            $currencyRepository,
            $channelRepository,
            $categoryFieldRepository
        );

        $this->measurementFamilyRepository = $measurementFamilyRepository;
        $this->attributeMeasurementRepository = $attributeMeasurementRepository;
    }

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
        $currentLang   = strtok($currentLocale, '_'); 

        $units = collect(
            $this->measurementFamilyRepository->getUnitsByFamilyCode($familyCode)
        )->map(function ($unit) use ($currentLocale, $currentLang) {

            $labels = $unit['labels'] ?? [];

             
            if (isset($labels[$currentLocale])) {
                $label = $labels[$currentLocale];
            }
          
            elseif ($firstLangMatch = collect($labels)->first(fn($_, $key) => str_starts_with($key, $currentLang))) {
                $label = $firstLangMatch;
            }
           
            else {
                $label = $unit['code'];
            }

            return (object) [
                'id'    => $unit['code'],
                'label' => $label,
                'code'  => $unit['code'],
            ];
        });

        $options = $this->formatCollection(
            $units,
            $page,
            50,
            $query,
            $queryParams
        );

        return response()->json($options);
    }

    protected function formatCollection(
        Collection $collection,
        int $page,
        int $limit,
        string $query,
        array $queryParams
    ): array {

        if (isset($queryParams['identifiers']['value'])) {
            $identifier = $queryParams['identifiers']['value'];

            $collection = $collection->sortByDesc(fn ($item) => $item->id === $identifier);
        }

        $paginated = $collection->forPage($page, $limit)->values();

        return [
            'options'  => $paginated,
            'page'     => $page,
            'lastPage' => ceil($collection->count() / $limit),
        ];
    }
}

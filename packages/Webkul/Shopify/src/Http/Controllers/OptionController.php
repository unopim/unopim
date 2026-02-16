<?php

namespace Webkul\Shopify\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Shopify\Repositories\ShopifyCredentialRepository;
use Webkul\Shopify\Repositories\ShopifyExportMappingRepository;

class OptionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ShopifyCredentialRepository $shopifyRepository,
        protected AttributeRepository $attributeRepository,
        protected ChannelRepository $channelRepository,
        protected CurrencyRepository $currencyRepository,
        protected LocaleRepository $localeRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected ShopifyExportMappingRepository $shopifyExportMappingRepository,
    ) {}

    /**
     * Return All credentials
     */
    public function listShopifyCredential(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);
        $query = request()->get('query') ?? null;
        $shopifyRepo = $this->shopifyRepository;
        if ($query) {
            $shopifyRepo = $shopifyRepo->where('shopUrl', 'LIKE', '%'.$query.'%');
        }

        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        $shopifyRepository = $shopifyRepo->where('active', 1);

        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];

            $shopifyRepository = $shopifyRepository->whereIn(
                'id',
                is_array($values) ? $values : [$values]
            );
        }

        $allActivateCredntial = $shopifyRepository->get()->toArray();
        $allCredential = [];

        foreach ($allActivateCredntial as $credentialArray) {
            $allCredential[] = [
                'id'    => $credentialArray['id'],
                'label' => $credentialArray['shopUrl'],
            ];
        }

        return new JsonResponse([
            'options' => $allCredential,
        ]);
    }

    /**
     * Return All Channels
     */
    public function listChannel(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);
        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        $channelRepository = $this->channelRepository;

        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];

            $channelRepository = $channelRepository->whereIn(
                'code',
                is_array($values) ? $values : [$values]
            );
        }

        $allActivateChannel = $channelRepository->get()->toArray();

        $allChannel = [];

        foreach ($allActivateChannel as $channel) {
            $allChannel[] = [
                'id'    => $channel['code'],
                'label' => $channel['name'] ?? $channel['code'],
            ];
        }

        return new JsonResponse([
            'options' => $allChannel,
        ]);
    }

    /**
     * Return All Currency
     */
    public function listCurrency(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);

        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        $currencyRepository = $this->currencyRepository->where('status', 1);

        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];

            $currencyRepository = $currencyRepository->whereIn(
                'code',
                is_array($values) ? $values : [$values]
            );
        }

        $allActivateCurrency = $currencyRepository->get()->toArray();

        $allCurrency = array_map(function ($item) {
            return [
                'id'    => $item['code'],
                'label' => $item['name'],
            ];
        }, $allActivateCurrency);

        return new JsonResponse([
            'options' => $allCurrency,
        ]);
    }

    /**
     * Return All Locale
     */
    public function listLocale(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);
        $localeRepository = $this->localeRepository;
        $query = request()->get('query');
        if ($query) {
            $localeRepository = $localeRepository->where('code', 'LIKE', '%'.$query.'%');
        }

        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        $localeRepository = $localeRepository->where('status', 1);

        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];

            $localeRepository = $localeRepository->whereIn(
                'code',
                is_array($values) ? $values : [$values]
            );
        }

        $allActivateLocale = $localeRepository->get()->toArray();

        $allLocale = array_map(function ($item) {
            return [
                'id'    => $item['code'],
                'label' => $item['name'],
            ];
        }, $allActivateLocale);

        return new JsonResponse([
            'options' => $allLocale,
        ]);
    }

    /**
     * List attributes based on entity and query filters.
     */
    public function listAttributes(): JsonResponse
    {
        $entityName = request()->get('entityName');
        $notInclude = request()->get(0) ?? '';
        $fieldName = request()->get(1) ?? '';
        $page = request()->get('page');
        $query = request()->get('query') ?? '';
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId', 'notInclude']);
        $attributeRepository = $this->attributeRepository;
        if (! empty($entityName)) {
            $entityName = json_decode($entityName);
            $attributeRepository = in_array('number', $entityName)
                ? $attributeRepository->whereIn('validation', $entityName)
                : $attributeRepository->whereIn('type', $entityName);
        }

        if (! empty($query)) {
            $attributeRepository = $attributeRepository->where('code', 'LIKE', '%'.$query.'%');
        }

        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];
            $attributeRepository = $attributeRepository->whereIn(
                $searchIdentifiers['columnName'],
                is_array($values) ? $values : [$values]
            );
            if (! empty($notInclude)) {
                $notIncludeValues = array_values(array_diff(array_values($notInclude), $values));
                $attributeRepository = $attributeRepository->whereNotIn('code', $notIncludeValues);
            }
        } else {
            if (! empty($notInclude)) {
                unset($notInclude[$fieldName]);
                $attributeRepository = $attributeRepository->whereNotIn('code', array_values($notInclude));
            }
        }

        $attributes = $attributeRepository->orderBy('id')->paginate(20, ['*'], 'paginate', $page);

        $formattedoptions = [];

        $currentLocaleCode = core()->getRequestedLocaleCode();

        foreach ($attributes as $attribute) {
            $translatedLabel = $attribute->translate($currentLocaleCode)?->name;
            $formattedoptions[] = [
                'id'         => $attribute->id,
                'code'       => $attribute->code,
                'type'       => $attribute?->type,
                'validation' => $attribute->validation,
                'label'      => ! empty($translatedLabel) ? $translatedLabel : "[{$attribute->code}]",
            ];
        }

        return new JsonResponse([
            'options'  => $formattedoptions,
            'page'     => $attributes->currentPage(),
            'lastPage' => $attributes->lastPage(),
        ]);
    }

    /**
     * List image attributes.
     */
    public function listImageAttributes(): JsonResponse
    {
        $query = request()->get('query') ?? '';
        $queryParams = request()->except(['page', 'query', 'attributeId']);
        $formattedoptions = [];
        if (isset($queryParams['entityName'])) {
            $attributeRepository = $this->attributeRepository->where('type', $queryParams['entityName']);
        } elseif (isset($queryParams['mediaType'])) {
            $attributeRepository = $this->attributeRepository->whereIn('type', [$queryParams['mediaType'], 'asset']);
        } else {
            $attributeRepository = $this->attributeRepository;
        }
        $currentLocaleCode = core()->getRequestedLocaleCode();

        if (! empty($query)) {
            $attributeRepository = $attributeRepository->where('code', 'LIKE', '%'.$query.'%');
        }

        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];

            $attributeRepository = $attributeRepository->whereIn(
                $searchIdentifiers['columnName'],
                is_array($values) ? $values : [$values]
            );
        }

        $attributes = $attributeRepository->get();

        foreach ($attributes as $attribute) {
            $translatedLabel = $attribute->translate($currentLocaleCode)?->name;
            $formattedoptions[] = [
                'id'    => $attribute->id,
                'code'  => $attribute->code,
                'label' => ! empty($translatedLabel) ? $translatedLabel : "[{$attribute->code}]",
            ];
        }

        return new JsonResponse([
            'options' => $formattedoptions,
        ]);
    }

    /**
     * List Gallery attributes.
     */
    public function listGalleryAttributes(): JsonResponse
    {
        $query = request()->get('query') ?? '';

        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);

        $attributeRepository = $this->attributeRepository->where('type', 'gallery');

        $currentLocaleCode = core()->getRequestedLocaleCode();

        if (! empty($query)) {
            $attributeRepository = $attributeRepository->where('code', 'LIKE', '%'.$query.'%');
        }

        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];

            $attributeRepository = $attributeRepository->whereIn(
                $searchIdentifiers['columnName'],
                is_array($values) ? $values : [$values]
            );
        }

        $attributes = $attributeRepository->get();

        $formattedoptions = [];

        foreach ($attributes as $attribute) {
            $translatedLabel = $attribute->translate($currentLocaleCode)?->name;
            $formattedoptions[] = [
                'id'    => $attribute->id,
                'code'  => $attribute->code,
                'label' => ! empty($translatedLabel) ? $translatedLabel : "[{$attribute->code}]",
            ];
        }

        return new JsonResponse([
            'options' => $formattedoptions,
        ]);
    }

    public function listMetafieldAttributes(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'attributeId']);

        $query = request()->get('query') ?? '';
        $credentialData = $this->shopifyRepository->find($queryParams[0]);
        $metaFieldAttr = array_merge($credentialData?->extras['productMetafield'] ?? [], $credentialData?->extras['productVariantMetafield'] ?? []);

        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];
        $entityName = $queryParams['entityName'];
        $attributeRepository = $this->attributeRepository->whereIn('code', $metaFieldAttr);

        if (! empty($entityName)) {
            $entityName = json_decode($entityName);
            $attributeRepository = in_array('number', $entityName)
                ? $attributeRepository->whereIn('validation', $entityName)
                : $attributeRepository->whereIn('type', $entityName);
        }

        if (! empty($query)) {
            $attributeRepository = $attributeRepository->where('code', 'LIKE', '%'.$query.'%');
        }

        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];

            $attributeRepository = $attributeRepository->whereIn(
                $searchIdentifiers['columnName'],
                is_array($values) ? $values : [$values]
            );
        }

        $attributes = $attributeRepository->get();

        $formattedoptions = [];
        $currentLocaleCode = core()->getRequestedLocaleCode();
        foreach ($attributes as $attribute) {
            $translatedLabel = $attribute->translate($currentLocaleCode)?->name;
            $formattedoptions[] = [
                'id'    => $attribute->id,
                'code'  => $attribute->code,
                'label' => ! empty($translatedLabel) ? $translatedLabel : "[{$attribute->code}]",
            ];
        }

        return new JsonResponse([
            'options' => $formattedoptions,
        ]);

    }

    public function selectedMetafieldAttributes(): JsonResponse
    {
        $id = request()->get('id');
        $shopifyMapping = $this->shopifyExportMappingRepository->find(3);
        $formattedoptions = [];

        $metaFieldMappings = $shopifyMapping->mapping['meta_fields'];
        $currentLocaleCode = core()->getRequestedLocaleCode();
        foreach ($metaFieldMappings as $key => $metaFieldMapping) {
            $metaFieldMapping = explode(',', $metaFieldMapping);
            $attributes = $this->attributeRepository->whereIn('code', $metaFieldMapping)->get();
            foreach ($attributes as $attribute) {
                $translatedLabel = $attribute->translate($currentLocaleCode)?->name;

                $formattedoptions[$key][] = [
                    'id'    => $attribute->id,
                    'code'  => $attribute->code,
                    'label' => ! empty($translatedLabel) ? $translatedLabel : "[{$attribute->code}]",
                ];
            }

        }

        return new JsonResponse($formattedoptions);
    }

    /**
     * List attribute Group.
     */
    public function listAttributeGroup(): JsonResponse
    {
        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);
        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];
        $attributeGroupRepository = $this->attributeGroupRepository;
        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];
            $attributeGroupRepository = $attributeGroupRepository->whereIn(
                'id',
                is_array($values) ? $values : [$values]
            );
        }
        $allAttributegroup = $attributeGroupRepository->get()->toArray();

        $attrGroupList = [];

        $attrGroupList = array_map(function ($item) {
            return [
                'id'    => $item['id'],
                'label' => $item['name'] ?? $item['code'],
            ];
        }, $allAttributegroup);

        return new JsonResponse([
            'options' => $attrGroupList,
        ]);
    }

    /**
     * List of family.
     */
    public function listShopifyFamily(): JsonResponse
    {
        $query = request()->get('query') ?? '';

        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);

        $attributeFamilyRepository = $this->attributeFamilyRepository;

        $currentLocaleCode = core()->getRequestedLocaleCode();

        if (! empty($query)) {
            $attributeFamilyRepository = $attributeFamilyRepository->where('code', 'LIKE', '%'.$query.'%');
        }

        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        if (! empty($searchIdentifiers)) {
            $values = $searchIdentifiers['values'] ?? [];

            $attributeFamilyRepository = $attributeFamilyRepository->whereIn(
                $searchIdentifiers['columnName'],
                is_array($values) ? $values : [$values]
            );
        }

        $attributesFamilies = $attributeFamilyRepository->get();

        $formattedoptions = [];

        foreach ($attributesFamilies as $attributesFamily) {
            $translatedLabel = $attributesFamily->translate($currentLocaleCode)?->name;
            $formattedoptions[] = [
                'id'    => $attributesFamily->id,
                'code'  => $attributesFamily->code,
                'label' => ! empty($translatedLabel) ? $translatedLabel : "[{$attributesFamily->code}]",
            ];
        }

        return new JsonResponse([
            'options' => $formattedoptions,
        ]);
    }
}

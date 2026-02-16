<?php

namespace Webkul\Shopify\Http\Controllers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Shopify\Helpers\ShopifyFields;
use Webkul\Shopify\Repositories\ShopifyCredentialRepository;
use Webkul\Shopify\Repositories\ShopifyExportMappingRepository;

class ImportMappingController extends Controller
{
    protected const IMPORT_MAPPING_RECORD_ID = 3;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ShopifyExportMappingRepository $shopifyExportMappingRepository,
        protected ShopifyCredentialRepository $shopifyCredentialRepository
    ) {}

    /**
     * Display Shopify import mappings.
     */
    public function index(int $id): View
    {
        $mappingFields = (new ShopifyFields)->getMappingField();
        $shopifyMapping = $this->shopifyExportMappingRepository->find($id);

        abort_if(! $shopifyMapping, 404);

        $shopifyCredentials = $this->shopifyCredentialRepository->all()->toArray();

        $attribute = [];
        $metafieldattrs = [];

        foreach ($shopifyMapping->mapping['shopify_connector_settings'] ?? [] as $row => $value) {
            $attribute[$row] = $value;
        }

        $formattedShopifyMapping = $attribute;
        $metafieldattr = [];

        $defaultMapping = [];
        foreach ($shopifyMapping->mapping['shopify_connector_defaults'] ?? [] as $row => $value) {
            $defaultMapping[$row] = $value;
        }

        $mediaMapping = [];
        foreach ($shopifyMapping->mapping['mediaMapping'] ?? [] as $row => $value) {
            $mediaMapping[$row] = $value;
        }

        return view('shopify::import.mapping.index', compact('mappingFields', 'formattedShopifyMapping', 'shopifyMapping', 'shopifyCredentials', 'mediaMapping', 'defaultMapping'));
    }

    /**
     * Create or update Shopify import mapping.
     */
    public function store(FormRequest $request)
    {
        $filteredData = array_filter($request->except(['_token', '_method']));
        $mappingFields = [];
        $filteredData = array_filter($filteredData, fn ($key) => ! str_starts_with($key, 'default_'), ARRAY_FILTER_USE_KEY);
        $mappingFieldss['mapping'] = [];
        $this->formatMediaMapping($filteredData, $mappingFields);
        $duplicates = array_filter(array_count_values($filteredData), fn ($count) => $count > 1);
        $duplicateKeys = array_keys(array_filter($filteredData, fn ($value) => isset($duplicates[$value])));

        if (! empty($duplicateKeys)) {
            $duplicateKeys = array_map(function ($value) {
                return 'default_'.$value;
            }, $duplicateKeys);

            $keysAsArray = array_fill_keys($duplicateKeys, 'Duplicate attribute mapping');

            return redirect()->route('admin.shopify.import-mappings', self::IMPORT_MAPPING_RECORD_ID)
                ->withErrors($keysAsArray)
                ->withInput();
        }

        foreach ($filteredData as $row => $value) {
            $sectionName = 'shopify_connector_settings';
            $mappingFields[$sectionName][$row] = $value;
            $mappingFieldss['mapping'] = $mappingFields;
        }

        $shopifyMapping = $this->shopifyExportMappingRepository->find(self::IMPORT_MAPPING_RECORD_ID);

        if ($shopifyMapping && $shopifyMapping->toArray()['mapping'] != $mappingFieldss['mapping']) {
            $shopifyMapping = $this->shopifyExportMappingRepository->update($mappingFieldss, self::IMPORT_MAPPING_RECORD_ID);
        }

        session()->flash('success', trans('shopify::app.shopify.import.mapping.created'));

        return redirect()->route('admin.shopify.import-mappings', self::IMPORT_MAPPING_RECORD_ID);
    }

    public function formatMediaMapping(array &$filteredData, array &$mappingFields)
    {
        $type = 'mediaType';
        $attributes = 'mediaAttributes';
        $section = 'mediaMapping';

        if (isset($filteredData[$type]) && isset($filteredData[$attributes])) {
            $mappingFields[$section][$type] = $filteredData[$type];
            $mappingFields[$section][$attributes] = $filteredData[$attributes];

            unset($filteredData[$attributes]);
            unset($filteredData[$type]);
        }
    }
}

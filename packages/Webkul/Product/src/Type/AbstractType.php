<?php

namespace Webkul\Product\Type;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Contracts\AttributeGroup;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Core\Traits\HtmlPurifier;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Repositories\AssociationTypeRepository;
use Webkul\Product\Repositories\ProductAssociationRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Validator\AssociationValidator;

abstract class AbstractType
{
    use HtmlPurifier;

    const PRODUCT_VALUES_KEY = 'values';

    const LOCALE_VALUES_KEY = 'locale_specific';

    const CHANNEL_VALUES_KEY = 'channel_specific';

    const CHANNEL_LOCALE_VALUES_KEY = 'channel_locale_specific';

    const COMMON_VALUES_KEY = 'common';

    const ASSOCIATION_VALUES_KEY = 'associations';

    const CATEGORY_VALUES_KEY = 'categories';

    const RELATED_ASSOCIATION_KEY = 'related_products';

    const UP_SELLS_ASSOCIATION_KEY = 'up_sells';

    const CROSS_SELLS_ASSOCIATION_KEY = 'cross_sells';

    const ASSOCIATION_SECTIONS = [
        self::RELATED_ASSOCIATION_KEY,
        self::UP_SELLS_ASSOCIATION_KEY,
        self::CROSS_SELLS_ASSOCIATION_KEY,
    ];

    /**
     * Product instance.
     *
     * @var \Webkul\Product\Models\Product
     */
    protected $product;

    /**
     * Products of this type can be copied in the admin backend.
     *
     * @var bool
     */
    protected $canBeCopied = true;

    /**
     * Has child products aka variants.
     *
     * @var bool
     */
    protected $hasVariants = false;

    /**
     * Product children price can be calculated or not.
     *
     * @var bool
     */
    protected $isChildrenCalculated = false;

    /**
     * Skip attribute for simple product type.
     *
     * @var array
     */
    protected $skipAttributes = [];

    /**
     * These blade files will be included in product edit page.
     *
     * @var array
     */
    protected $additionalViews = [];

    /**
     * Create a new product type instance.
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected ProductRepository $productRepository,
        protected FileStorer $fileStorer,
    ) {}

    /**
     * Create product.
     *
     * @return Product
     */
    public function create(array $data)
    {
        $product = $this->productRepository->getModel()->fill($data);

        $product->values = [self::COMMON_VALUES_KEY => ['sku' => $product->sku]];

        $product->save();

        return $product;
    }

    /**
     * Update product.
     *
     * A submitted `associations` payload supersedes the legacy flat keys for the
     * types it carries; it is validated and sku-resolved here, BEFORE the save,
     * so an invalid link aborts the whole update with nothing persisted.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return Product
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $product = $this->productRepository->find($id);

        if (! empty($data[self::PRODUCT_VALUES_KEY])) {
            $data = $this->prepareProductValues($data, $product);
        }

        $productValues = ! empty($data[self::PRODUCT_VALUES_KEY]) ? $data[self::PRODUCT_VALUES_KEY] : ($product->values ?? []);

        if (! empty($data[self::CATEGORY_VALUES_KEY])) {
            $productValues[self::CATEGORY_VALUES_KEY] = $data[self::CATEGORY_VALUES_KEY];
        }

        $unifiedAssociations = $data[self::ASSOCIATION_VALUES_KEY] ?? [];

        if (! is_array($unifiedAssociations)) {
            $unifiedAssociations = [];
        }

        $resolvedRichAssociations = [];

        if (! empty($unifiedAssociations)) {
            [$legacySkuLists, $resolvedRichAssociations] = $this->prepareRichAssociations($unifiedAssociations, $product);

            foreach ($legacySkuLists as $section => $skus) {
                $productValues[self::ASSOCIATION_VALUES_KEY][$section] = $skus;
            }
        }

        if (
            ! array_key_exists(self::UP_SELLS_ASSOCIATION_KEY, $unifiedAssociations)
            && ! empty($data[self::UP_SELLS_ASSOCIATION_KEY])
        ) {
            $productValues[self::ASSOCIATION_VALUES_KEY][self::UP_SELLS_ASSOCIATION_KEY] = $data[self::UP_SELLS_ASSOCIATION_KEY];
        }

        if (
            ! array_key_exists(self::CROSS_SELLS_ASSOCIATION_KEY, $unifiedAssociations)
            && ! empty($data[self::CROSS_SELLS_ASSOCIATION_KEY])
        ) {
            $productValues[self::ASSOCIATION_VALUES_KEY][self::CROSS_SELLS_ASSOCIATION_KEY] = $data[self::CROSS_SELLS_ASSOCIATION_KEY];
        }

        if (
            ! array_key_exists(self::RELATED_ASSOCIATION_KEY, $unifiedAssociations)
            && ! empty($data[self::RELATED_ASSOCIATION_KEY])
        ) {
            $productValues[self::ASSOCIATION_VALUES_KEY][self::RELATED_ASSOCIATION_KEY] = $data[self::RELATED_ASSOCIATION_KEY];
        }

        if (! isset($productValues[self::COMMON_VALUES_KEY]['sku'])) {
            $productValues[self::COMMON_VALUES_KEY]['sku'] = $data['sku'] ?? $product->sku;
        }

        $persist = function () use ($product, $productValues, $data): void {
            $product->values = $productValues;

            $product->fill($data);

            $product->wasDirtyOnUpdate = $product->isDirty();

            if ($product->wasDirtyOnUpdate) {
                $product->save();
            }
        };

        if ($product->parent_id && ! empty($productValues[self::COMMON_VALUES_KEY])) {
            $this->productRepository->guardVariantLevelWrite($product, $productValues[self::COMMON_VALUES_KEY], $persist);
        } else {
            $persist();
        }

        if ($product->id) {
            $this->syncAssociationLinks($product, $productValues, array_keys($unifiedAssociations));

            if (! empty($resolvedRichAssociations)) {
                $this->syncRichAssociations($product->id, $resolvedRichAssociations);
            }
        }

        return $product;
    }

    /**
     * Validate each link's `additional_data` and resolve its `sku` to a product id.
     *
     * A type key present with zero rows is authoritative (the form's `__present`
     * sentinel), so clearing the last link clears the type. Public: write paths
     * bypassing `update()` call it to validate BEFORE persisting anything.
     *
     * @return array{0: array<string,array<int,string>>, 1: array<string,array{association_type_id:int,links:array}>}
     */
    public function prepareRichAssociations(array $associations, Product $product): array
    {
        $associationTypeRepository = app(AssociationTypeRepository::class);

        $associationValidator = app(AssociationValidator::class);

        $legacySkuLists = [];

        $resolvedAssociations = [];

        foreach ($associations as $typeCode => $links) {
            if (! is_array($links)) {
                continue;
            }

            unset($links['__present']);

            $associationType = $associationTypeRepository->findByCode($typeCode);

            if (! $associationType) {
                continue;
            }

            $skus = [];

            $additionalDataBySku = [];

            foreach ($links as $link) {
                $sku = $link['sku'] ?? null;

                if (! is_string($sku) || $sku === '') {
                    continue;
                }

                $additionalData = $link['additional_data'] ?? [];

                $associationValidator->validate((int) $associationType->id, $additionalData);

                $skus[] = $sku;

                $additionalDataBySku[$sku] = empty($additionalData) ? null : $additionalData;
            }

            $skus = array_values(array_unique($skus));

            if (in_array($typeCode, self::ASSOCIATION_SECTIONS, true)) {
                $legacySkuLists[$typeCode] = $skus;
            }

            $resolvedProducts = empty($skus)
                ? collect()
                : $this->productRepository->findWhereIn('sku', $skus, ['id', 'sku']);

            $resolvedLinks = [];

            foreach ($resolvedProducts as $resolvedProduct) {
                $relatedProductId = (int) $resolvedProduct->id;

                if ($relatedProductId === (int) $product->id) {
                    continue;
                }

                $resolvedLinks[] = [
                    'related_product_id' => $relatedProductId,
                    'position'           => null,
                    'additional_data'    => $additionalDataBySku[$resolvedProduct->sku] ?? null,
                ];
            }

            $resolvedAssociations[$typeCode] = [
                'association_type_id' => (int) $associationType->id,
                'links'               => $resolvedLinks,
            ];
        }

        return [$legacySkuLists, $resolvedAssociations];
    }

    /**
     * Sync resolved links per type, preserving each link's `additional_data`.
     *
     * A failure for one type is reported, not rethrown: the other types and the
     * product save that already happened must not be affected. Public for the
     * same reason as `prepareRichAssociations()`.
     *
     * @param  array<string,array{association_type_id:int,links:array}>  $resolvedAssociations
     */
    public function syncRichAssociations(int $productId, array $resolvedAssociations): void
    {
        $associationRepository = app(ProductAssociationRepository::class);

        foreach ($resolvedAssociations as $associationData) {
            try {
                $associationRepository->syncTypeWithData(
                    $productId,
                    $associationData['association_type_id'],
                    $associationData['links']
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Mirror the legacy JSON `values['associations']` into the `product_associations`
     * link table (dual-write), skipping `$excludeSections` already synced richly so
     * no type is synced twice. A failure is reported, not rethrown: the product save
     * has already happened. Public so write paths bypassing `update()` can mirror too.
     */
    public function syncAssociationLinks(Product $product, array $productValues, array $excludeSections = []): void
    {
        $associationRepository = app(ProductAssociationRepository::class);

        foreach (self::ASSOCIATION_SECTIONS as $section) {
            if (in_array($section, $excludeSections, true)) {
                continue;
            }

            try {
                $associationRepository->syncFromSkuList(
                    $product->id,
                    $section,
                    $productValues[self::ASSOCIATION_VALUES_KEY][$section] ?? []
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Modifies the Product values in product data.
     *
     * Values arrive keyed by scope — `common`, `channel_specific`, `locale_specific`
     * and `channel_locale_specific` — and are walked by reference so the nested arrays
     * are not looked up repeatedly.
     */
    public function prepareProductValues(
        array $data,
        Product $product,
        ?string $currentLocaleCode = null,
        ?string $currentChannelCode = null
    ): array {
        if (empty($data[self::PRODUCT_VALUES_KEY])) {
            return $data;
        }

        $currentLocaleCode ??= core()->getRequestedLocaleCode();

        $currentChannelCode ??= core()->getRequestedChannelCode();

        /**
         * when the array key for common or locale is not present null is returned
         * but refernce is still maintained
         */
        $commonValues = &$data[self::PRODUCT_VALUES_KEY][self::COMMON_VALUES_KEY];

        $localeValues = &$data[self::PRODUCT_VALUES_KEY][self::LOCALE_VALUES_KEY];

        $channelValues = &$data[self::PRODUCT_VALUES_KEY][self::CHANNEL_VALUES_KEY];

        $channelAndLocaleValues = &$data[self::PRODUCT_VALUES_KEY][self::CHANNEL_LOCALE_VALUES_KEY];

        $productLocaleValues = $product->values[self::LOCALE_VALUES_KEY] ?? [];

        $productChannelValues = $product->values[self::CHANNEL_VALUES_KEY] ?? [];

        $productChannelLocaleValues = $product->values[self::CHANNEL_LOCALE_VALUES_KEY] ?? [];

        if (! empty($channelAndLocaleValues[$currentChannelCode][$currentLocaleCode])) {
            $productChannelLocaleValues[$currentChannelCode][$currentLocaleCode] = $this->processValues(
                productId: $product->id,
                values: $channelAndLocaleValues[$currentChannelCode][$currentLocaleCode],
                productValues: ($productChannelLocaleValues[$currentChannelCode][$currentLocaleCode] ?? [])
            );

            if (empty($productChannelLocaleValues[$currentChannelCode][$currentLocaleCode])) {
                unset($productChannelLocaleValues[$currentChannelCode][$currentLocaleCode]);
            }
        }

        $channelAndLocaleValues = $productChannelLocaleValues;

        $channelAndLocaleValues = is_array($channelAndLocaleValues)
            ? array_filter($channelAndLocaleValues)
            : $channelAndLocaleValues;

        if (! empty($channelValues[$currentChannelCode])) {
            $productChannelValues[$currentChannelCode] = $this->processValues(
                productId: $product->id,
                values: $channelValues[$currentChannelCode],
                productValues: ($productChannelValues[$currentChannelCode] ?? [])
            );
        }

        $channelValues = $productChannelValues;

        $channelValues = is_array($channelValues)
            ? array_filter($channelValues)
            : $channelValues;

        if (! empty($localeValues[$currentLocaleCode])) {
            $productLocaleValues[$currentLocaleCode] = $this->processValues(
                productId: $product->id,
                values: $localeValues[$currentLocaleCode],
                productValues: ($productLocaleValues[$currentLocaleCode] ?? [])
            );
        }

        $localeValues = $productLocaleValues;

        $localeValues = is_array($localeValues)
            ? array_filter($localeValues)
            : $localeValues;

        if (! empty($commonValues)) {
            $commonValues = $this->processValues(
                productId: $product->id,
                values: $commonValues,
                productValues: ($product->values[self::COMMON_VALUES_KEY] ?? []),
                isCommonAttribute: true
            );
        } elseif (isset($product->values[self::COMMON_VALUES_KEY])) {
            $commonValues = $product->values[self::COMMON_VALUES_KEY];
        }

        if (empty($commonValues)) {
            unset($data[self::PRODUCT_VALUES_KEY][self::COMMON_VALUES_KEY]);
        }

        if (empty($localeValues)) {
            unset($data[self::PRODUCT_VALUES_KEY][self::LOCALE_VALUES_KEY]);
        }

        if (empty($channelValues)) {
            unset($data[self::PRODUCT_VALUES_KEY][self::CHANNEL_VALUES_KEY]);
        }

        if (empty($channelAndLocaleValues)) {
            unset($data[self::PRODUCT_VALUES_KEY][self::CHANNEL_LOCALE_VALUES_KEY]);
        }

        ksort($data[self::PRODUCT_VALUES_KEY], SORT_NATURAL);

        return $data;
    }

    /**
     * Process price values for common attribute
     */
    public function processCommonPriceValues(string $field, array $newData, array $oldData): array
    {
        return array_merge($oldData[$field] ?? [], $newData);
    }

    /**
     * process values by value type like files and images
     */
    protected function processValues(int $productId, array $values, array $productValues = [], bool $isCommonAttribute = false): array
    {
        $values = array_filter(
            $productValues === []
                ? $values
                : array_merge($productValues, $values)
        );

        $attributes = $this->attributeRepository->findWhereIn('code', array_keys($values))->keyBy('code');

        foreach ($values as $field => $fieldValue) {
            $attribute = $attributes->get($field);

            if (
                is_string($fieldValue)
                && $fieldValue !== ''
                && $attribute?->type === 'textarea'
                && $attribute->enable_wysiwyg
            ) {
                $values[$field] = $fieldValue = $this->purifyText($fieldValue);
            }

            if (is_array($fieldValue)) {
                $type = $attribute?->type;

                if (in_array($type, ['image', 'gallery', 'file'], true)) {
                    $path = 'product'.DIRECTORY_SEPARATOR.$productId.DIRECTORY_SEPARATOR.$field;

                    if ($type === 'gallery') {
                        $values[$field] = array_map(function ($val) use ($path) {
                            if ($val instanceof UploadedFile && ! $val->isValid()) {
                                if (in_array($val->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) {
                                    throw ValidationException::withMessages([
                                        $path => [trans('admin::app.common.file-size-exceeds', ['max' => ini_get('upload_max_filesize')])],
                                    ]);
                                }

                                return $val;
                            }

                            return $val instanceof UploadedFile
                                ? $this->fileStorer->store($path, $val, [FileStorer::HASHED_FOLDER_NAME_KEY => true])
                                : $val;
                        }, $fieldValue);

                        ksort($values[$field]);

                        $values[$field] = array_values($values[$field]);
                    } elseif ($fieldValue !== [] && current($fieldValue) instanceof UploadedFile) {
                        $uploadedFile = current($fieldValue);

                        if (! $uploadedFile->isValid()) {
                            if (in_array($uploadedFile->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) {
                                throw ValidationException::withMessages([
                                    $field => [trans('admin::app.common.file-size-exceeds', ['max' => ini_get('upload_max_filesize')])],
                                ]);
                            }
                        } else {
                            $values[$field] = $this->fileStorer->store($path, $uploadedFile, [FileStorer::HASHED_FOLDER_NAME_KEY => true]);
                        }
                    }

                    continue;
                }
            }

            if (
                $isCommonAttribute
                && $attributes->get($field)?->type === AttributeTypes::PRICE_ATTRIBUTE_TYPE
            ) {
                $fieldValue = $this->processCommonPriceValues($field, $fieldValue, $productValues);
            }

            if (is_array($fieldValue)) {
                $fieldValue = array_filter($fieldValue);

                if ($fieldValue === []) {
                    unset($values[$field]);
                } else {
                    $values[$field] = array_is_list($fieldValue) ? implode(',', $fieldValue) : $fieldValue;
                }
            }
        }

        return $values;
    }

    /**
     * Copy product.
     *
     * @return Product
     *
     * @throws \Exception
     */
    public function copy()
    {
        if (! $this->canBeCopied()) {
            throw new \Exception(trans('product::app.response.product-can-not-be-copied', ['type' => $this->product->type]));
        }

        $copiedProduct = $this->product
            ->replicate()
            ->fill(['sku' => 'temporary-sku-'.substr(md5(microtime()), 0, 6)]);

        $values = $this->filterUniqueAttributeValues($copiedProduct->values ?? []);

        $values[self::COMMON_VALUES_KEY]['sku'] = $copiedProduct->sku;

        $copiedProduct->values = $values;

        $copiedProduct->save();

        $this->copyMediaValues($copiedProduct);

        if ($copiedProduct->id) {
            $this->syncAssociationLinks($copiedProduct, $values);
        }

        $this->copyRelationships($copiedProduct);

        return $copiedProduct;
    }

    protected function copyMediaValues($copiedProduct): void
    {
        $mediaCodes = app(AttributeRepository::class)
            ->whereIn('type', [
                AttributeTypes::IMAGE_ATTRIBUTE_TYPE,
                AttributeTypes::FILE_ATTRIBUTE_TYPE,
                AttributeTypes::GALLERY_ATTRIBUTE_TYPE,
            ])
            ->pluck('code')
            ->all();

        if ($mediaCodes === []) {
            return;
        }

        $values = $copiedProduct->values ?? [];
        $changed = false;

        array_walk_recursive($values, function (&$value, $key) use ($mediaCodes, $copiedProduct, &$changed): void {
            if (! in_array($key, $mediaCodes, true) || ! is_string($value) || $value === '') {
                return;
            }

            $paths = array_filter(array_map(trim(...), explode(',', $value)));

            $copied = [];

            foreach ($paths as $path) {
                $copied[] = $this->copyMediaFile($path, $copiedProduct->id, $key);
            }

            $updated = implode(',', $copied);

            if ($updated !== $value) {
                $value = $updated;
                $changed = true;
            }
        });

        if ($changed) {
            $copiedProduct->values = $values;

            $copiedProduct->save();
        }
    }

    protected function copyMediaFile(string $path, int $productId, string $attributeCode): string
    {
        $target = 'product/'.$productId.'/'.$attributeCode.'/'.basename($path);

        if ($path === $target) {
            return $path;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return $path;
        }

        $disk->put($target, $disk->get($path));

        return $target;
    }

    /**
     * Copy relationships.
     *
     * @param  \Webkul\Product\Models\Product  $product
     * @return void
     */
    protected function copyRelationships($product)
    {
        $attributesToSkip = config('products.copy.skip_attributes', []);

        if (! in_array('product_relations', $attributesToSkip)) {
            DB::table('product_relations')->insert([
                'parent_id' => $this->product->id,
                'child_id'  => $product->id,
            ]);
        }
    }

    /**
     * Specify type instance product.
     *
     * @param  Product  $product
     * @return AbstractType
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Returns children ids.
     *
     * @return array
     */
    public function getChildrenIds()
    {
        return [];
    }

    /**
     * Return true if this product can have variants.
     *
     * @return bool
     */
    public function hasVariants()
    {
        return $this->hasVariants;
    }

    /**
     * Product children price can be calculated or not.
     *
     * @return bool
     */
    public function isChildrenCalculated()
    {
        return $this->isChildrenCalculated;
    }

    /**
     * Is the administrator able to copy products of this type in the admin backend?
     */
    public function canBeCopied(): bool
    {
        return $this->canBeCopied;
    }

    /**
     * Retrieve product attributes.
     *
     * @param  AttributeGroup|null  $group
     * @param  bool  $skipSuperAttribute
     * @return Collection
     */
    public function getEditableAttributes($group = null, $skipSuperAttribute = true)
    {
        if ($skipSuperAttribute) {
            $this->skipAttributes = array_merge(
                $this->product->super_attributes->pluck('code')->toArray(),
                $this->skipAttributes
            );
        }

        if (! $group) {
            return $this->product->attribute_family->customAttributes()->whereNotIn(
                'attributes.code',
                $this->skipAttributes
            )->get();
        }

        return $this->product->attribute_family
            ->customAttributesByGroup()
            ->get($group->id, new EloquentCollection)
            ->whereNotIn('code', $this->skipAttributes);
    }

    /**
     * Retrieve the editable attributes of a single group.
     *
     * Kept separate from {@see getEditableAttributes()} because that one reads
     * the family's memoised full attribute set, which is what makes a large
     * family unrenderable.
     *
     * @return Collection<int, Attribute>
     */
    public function getEditableAttributesForGroup(int $groupId, bool $skipSuperAttribute = true): Collection
    {
        if ($skipSuperAttribute) {
            $this->skipAttributes = array_merge(
                $this->product->super_attributes->pluck('code')->toArray(),
                $this->skipAttributes
            );
        }

        return $this->product->attribute_family
            ->customAttributesForGroup($groupId)
            ->whereNotIn('code', $this->skipAttributes)
            ->values();
    }

    /**
     * Retrieve unique attributes for the product according to family.
     */
    public function getUniqueAttributes(bool $skipSuperAttribute = true): ?Collection
    {
        $uniqueAttributesQb = $this->product->attribute_family->customAttributes()->where('attributes.is_unique', 1);

        if ($skipSuperAttribute) {
            $this->skipAttributes = array_merge(
                $this->product->super_attributes->pluck('code')->toArray(),
                $this->skipAttributes
            );

            $uniqueAttributesQb->whereNotIn('attributes.code', $this->skipAttributes);
        }

        return $uniqueAttributesQb->get();
    }

    /**
     * Filter out the unique attribute values when copying the product or creating a variant.
     */
    public function filterUniqueAttributeValues(array $productValues, Collection|array $uniqueAttributes = []): array
    {
        if (! $uniqueAttributes) {
            $uniqueAttributes = $this->getUniqueAttributes();
        }

        $currentChannel = core()->getRequestedChannelCode();

        $currentLocale = core()->getRequestedLocaleCode();

        foreach ($uniqueAttributes as $unique) {
            if ($unique->code === 'sku') {
                continue;
            }

            $uniqueValue = $unique->getValueFromProductValues($productValues, $currentChannel, $currentLocale);

            if (empty($uniqueValue)) {
                continue;
            }

            $unique->setProductValue('', $productValues, $currentChannel, $currentLocale);
        }

        return $productValues;
    }

    /**
     * Returns additional views.
     *
     * @return array
     */
    public function getAdditionalViews()
    {
        return $this->additionalViews;
    }

    /**
     * Returns validation rules.
     *
     * @return array
     */
    public function getTypeValidationRules()
    {
        return [];
    }

    /**
     * Compare options.
     *
     * @return bool
     */
    public function compareOptions(array $options1, array $options2)
    {
        if ($this->product->id != $options2['product_id']) {
            return false;
        }
        if (isset($options1['parent_id'])
        && isset($options2['parent_id'])) {
            return $options1['parent_id'] == $options2['parent_id'];
        }
        if (isset($options1['parent_id'])
        && ! isset($options2['parent_id'])) {
            return false;
        }

        return ! isset($options2['parent_id']) || isset($options1['parent_id']);
    }

    /**
     * Returns additional information for items.
     *
     * @param  array  $data
     * @return array
     */
    public function getAdditionalOptions($data)
    {
        return $data;
    }

    /**
     * Update the product with values key without further formatting
     */
    public function updateWithValues(array $data, string|int $id): Product
    {
        $product = $this->productRepository->find($id);

        $product->values = $data[self::PRODUCT_VALUES_KEY];

        $product->fill($data);

        $product->wasDirtyOnUpdate = $product->isDirty();

        if ($product->wasDirtyOnUpdate) {
            $product->save();
        }

        if ($product->id) {
            $this->syncAssociationLinks($product, $product->values ?? []);
        }

        return $product;
    }
}

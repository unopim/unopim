<?php

namespace Webkul\Product\Type;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Attribute\Rules\AttributeTypes;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Repositories\ProductRepository;

abstract class AbstractType
{
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
     * Is a composite product type.
     *
     * @var bool
     */
    protected $isComposite = false;

    /**
     * Is a stockable product type.
     *
     * @var bool
     */
    protected $isStockable = true;

    /**
     * Show quantity box.
     *
     * @var bool
     */
    protected $showQuantityBox = false;

    /**
     * Is product have sufficient quantity.
     *
     * @var bool
     */
    protected $haveSufficientQuantity = true;

    /**
     * Product can be moved from wishlist to cart or not.
     *
     * @var bool
     */
    protected $canBeMovedFromWishlistToCart = true;

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
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected ProductRepository $productRepository,
        protected FileStorer $fileStorer,
    ) {}

    /**
     * Create product.
     *
     * @return \Webkul\Product\Contracts\Product
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
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Product\Contracts\Product
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $product = $this->productRepository->find($id);

        if (! empty($data[self::PRODUCT_VALUES_KEY])) {
            $data = $this->prepareProductValues($data, $product);

            $product->values = $data[self::PRODUCT_VALUES_KEY];
        }

        $productValues = $product->values;

        if (! empty($data[self::CATEGORY_VALUES_KEY])) {
            $productValues[self::CATEGORY_VALUES_KEY] = $data[self::CATEGORY_VALUES_KEY];
        }

        if (! empty($data[self::UP_SELLS_ASSOCIATION_KEY])) {
            $productValues[self::ASSOCIATION_VALUES_KEY][self::UP_SELLS_ASSOCIATION_KEY] = $data[self::UP_SELLS_ASSOCIATION_KEY];
        }

        if (! empty($data[self::CROSS_SELLS_ASSOCIATION_KEY])) {
            $productValues[self::ASSOCIATION_VALUES_KEY][self::CROSS_SELLS_ASSOCIATION_KEY] = $data[self::CROSS_SELLS_ASSOCIATION_KEY];
        }

        if (! empty($data[self::RELATED_ASSOCIATION_KEY])) {
            $productValues[self::ASSOCIATION_VALUES_KEY][self::RELATED_ASSOCIATION_KEY] = $data[self::RELATED_ASSOCIATION_KEY];
        }

        if (! isset($productValues[self::COMMON_VALUES_KEY]['status'])) {
            $productValues[self::COMMON_VALUES_KEY]['status'] = 'false';
        }

        if (! isset($productValues[self::COMMON_VALUES_KEY]['sku'])) {
            $productValues[self::COMMON_VALUES_KEY]['sku'] = $data['sku'] ?? $product->sku;
        }

        $product->values = $productValues;

        if ($product->isDirty()) {
            $product->update($data);
        }

        return $product;
    }

    /**
     * Modifies the Product values in product data
     *
     * Reference has been used in this to avoid redundant access of array
     *
     * Additional Values Format
     *
     * 'values' => [
     *     'common' => [
     *          'attribute_code' => 'value',
     *      ],
     *      'channel_locale_specific' => [
     *          'channelCode' => [
     *              'localeCode' => [
     *                  'attribute_code' => 'value',
     *              ],
     *          ],
     *      ],
     *      'channel_specific' => [
     *          'channelCode' => [
     *              'attribute_code' => 'value',
     *          ],
     *      ],
     *      'locale_specific' => [
     *          'localeCode' => [
     *              'attribute_code' => 'value',
     *          ],
     *      ],
     * ]
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

        /**
         * For Channel And Locale Values
         */
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

        /**
         * For Channel Values
         */
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

        /**
         * For Locale Values
         */
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

        /**
         * For common values
         */
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
            ! empty($productValues)
                ? array_merge($productValues, $values)
                : $values
        );

        foreach ($values as $field => $fieldValue) {
            if (is_array($fieldValue)) {
                $attribute = $this->attributeRepository->findOneByField('code', $field);
                $type = $attribute?->type;

                if ($type === 'image' || $type === 'gallery' || $type === 'file') {
                    $path = 'product'.DIRECTORY_SEPARATOR.$productId.DIRECTORY_SEPARATOR.$field;

                    if ($type === 'gallery') {
                        $values[$field] = array_map(function ($val) use ($path) {
                            return $val instanceof UploadedFile
                                ? $this->fileStorer->store($path, $val, [FileStorer::HASHED_FOLDER_NAME_KEY => true])
                                : $val;
                        }, $fieldValue);

                        $values[$field] = array_values($values[$field]);
                    } elseif (! empty($fieldValue) && current($fieldValue) instanceof UploadedFile) {
                        $values[$field] = $this->fileStorer->store($path, current($fieldValue), [FileStorer::HASHED_FOLDER_NAME_KEY => true]);
                    }

                    continue;
                }
            }

            if (
                $isCommonAttribute
                && $this->attributeRepository->findWhere([
                    'type' => AttributeTypes::PRICE_ATTRIBUTE_TYPE,
                    'code' => $field,
                ])->first()?->toArray()
            ) {

                $fieldValue = $this->processCommonPriceValues($field, $fieldValue, $productValues);
            }

            if (is_array($fieldValue)) {
                $fieldValue = array_filter($fieldValue);

                if (empty($fieldValue)) {
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
     * @return \Webkul\Product\Contracts\Product
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

        $values = $copiedProduct->values;

        $values[self::COMMON_VALUES_KEY]['sku'] = $copiedProduct->sku;

        $copiedProduct->values = $values;

        $copiedProduct->save();

        $this->copyRelationships($copiedProduct);

        return $copiedProduct;
    }

    /**
     * Copy relationships.
     *
     * @param  \Webkul\Product\Models\Product  $product
     * @return void
     */
    protected function copyRelationships($product)
    {
        $attributesToSkip = config('products.copy.skip_attributes') ?? [];

        if (! in_array('product_relations', $attributesToSkip)) {
            DB::table('product_relations')->insert([
                'parent_id' => $this->product->id,
                'child_id'  => $product->id,
            ]);
        }
    }

    /**
     * Copy product image video.
     */
    private function copyMedia($product, $media, $copiedMedia): void
    {
        $path = explode('/', $media->path);

        $copiedMedia->path = 'product/'.$product->id.'/'.end($path);

        $copiedMedia->save();

        Storage::makeDirectory('product/'.$product->id);

        Storage::copy($media->path, $copiedMedia->path);
    }

    /**
     * Specify type instance product.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return \Webkul\Product\Type\AbstractType
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
     * Return true if this product can be composite.
     *
     * @return bool
     */
    public function isComposite()
    {
        return $this->isComposite;
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
     * @param  \Webkul\Attribute\Contracts\Group  $group
     * @param  bool  $skipSuperAttribute
     * @return \Illuminate\Support\Collection
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

        return $group->customAttributes($this->product->attribute_family->id)->whereNotIn('code', $this->skipAttributes);
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
     * @param  array  $options1
     * @param  array  $options2
     * @return bool
     */
    public function compareOptions($options1, $options2)
    {
        if ($this->product->id != $options2['product_id']) {
            return false;
        } else {
            if (
                isset($options1['parent_id'])
                && isset($options2['parent_id'])
            ) {
                return $options1['parent_id'] == $options2['parent_id'];
            } elseif (
                isset($options1['parent_id'])
                && ! isset($options2['parent_id'])
            ) {
                return false;
            } elseif (
                isset($options2['parent_id'])
                && ! isset($options1['parent_id'])
            ) {
                return false;
            }
        }

        return true;
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

        if (! isset($data[self::COMMON_VALUES_KEY]['status'])) {
            $data[self::COMMON_VALUES_KEY]['status'] = 'false';
        }

        if (! isset($data[self::COMMON_VALUES_KEY]['sku'])) {
            $data[self::COMMON_VALUES_KEY]['sku'] = $data['sku'] ?? $product->sku;
        }

        $product->values = $data[self::PRODUCT_VALUES_KEY];

        $product->fill($data);

        if ($product->isDirty()) {
            $product->save();
        }

        return $product;
    }
}

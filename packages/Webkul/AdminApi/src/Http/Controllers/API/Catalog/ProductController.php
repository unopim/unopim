<?php

namespace Webkul\AdminApi\Http\Controllers\API\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Webkul\AdminApi\Http\Controllers\API\ApiController;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Product\Facades\ValueSetter;
use Webkul\Product\Helpers\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Type\AbstractType as ProductAbstractType;
use Webkul\Product\Validator\ProductValuesValidator;

class ProductController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected ProductValuesValidator $valuesValidator,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository,
    ) {}

    /**
     * Updates a product in the system using the provided data and ID.
     *
     * @param  array  $data  The data to be used for updating the product.
     * @param  Product  $id  The unique identifier of the product to be updated.
     * @return \Webkul\Product\Models\Product The updated product model.
     */
    protected function updateProduct(array $data, Product $product): Product
    {
        $attributes = $product->getEditableAttributes()
            ->where('enable_wysiwyg', '==', 1)
            ->where('type', '==', 'textarea');

        $data['values'] = $this->sanitizeData($data['values'], $attributes);

        if (isset($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::COMMON_VALUES_KEY])) {
            ValueSetter::setCommon($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::COMMON_VALUES_KEY]);
        }

        if (isset($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::LOCALE_VALUES_KEY])) {
            ValueSetter::setLocaleSpecific($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::LOCALE_VALUES_KEY]);
        }

        if (isset($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::CHANNEL_VALUES_KEY])) {
            ValueSetter::setChannelSpecific($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::CHANNEL_VALUES_KEY]);
        }

        if (isset($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::CHANNEL_LOCALE_VALUES_KEY])) {
            ValueSetter::setChannelLocaleSpecific($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::CHANNEL_LOCALE_VALUES_KEY]);
        }

        if (isset($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::CATEGORY_VALUES_KEY])) {
            ValueSetter::setCategories($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::CATEGORY_VALUES_KEY]);
        }

        if (isset($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::ASSOCIATION_VALUES_KEY][ProductAbstractType::UP_SELLS_ASSOCIATION_KEY])) {
            ValueSetter::setUpSellsAssociation($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::ASSOCIATION_VALUES_KEY][ProductAbstractType::UP_SELLS_ASSOCIATION_KEY]);
        }

        if (isset($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::ASSOCIATION_VALUES_KEY][ProductAbstractType::CROSS_SELLS_ASSOCIATION_KEY])) {
            ValueSetter::setCrossSellsAssociation($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::ASSOCIATION_VALUES_KEY][ProductAbstractType::CROSS_SELLS_ASSOCIATION_KEY]);
        }

        if (isset($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::ASSOCIATION_VALUES_KEY][ProductAbstractType::RELATED_ASSOCIATION_KEY])) {
            ValueSetter::setRelatedAssociation($data[ProductAbstractType::PRODUCT_VALUES_KEY][ProductAbstractType::ASSOCIATION_VALUES_KEY][ProductAbstractType::RELATED_ASSOCIATION_KEY]);
        }

        $data['values'] = ValueSetter::getValues();

        $product->values = ValueSetter::getValues();

        if (isset($data['status'])) {
            $product->status = (int) $data['status'];
        }

        if ($product->isDirty()) {
            $product->update($data);
        }

        if ($product->type == 'configurable') {
            $this->updateVaraints($product, $data);
        }

        $product->refresh();

        return $product;
    }

    public function sanitizeData($product, $attributes)
    {
        foreach ($attributes as $attribute) {
            if ($attribute->value_per_channel && $attribute->value_per_locale) {
                foreach ($product[ProductAbstractType::CHANNEL_LOCALE_VALUES_KEY] ?? [] as $channel => $locales) {
                    foreach ($locales ?? [] as $locale => $value) {
                        if (! empty($value[$attribute->code])) {
                            $val = htmlspecialchars($value[$attribute->code], ENT_QUOTES, 'UTF-8');
                            $attribute->setProductValue($val, $product, $channel, $locale);
                        }
                    }
                }
            } elseif ($attribute->value_per_channel) {
                foreach ($product[ProductAbstractType::CHANNEL_VALUES_KEY] ?? [] as $channel => $value) {
                    if (! empty($value[$attribute->code])) {
                        $val = htmlspecialchars($value[$attribute->code], ENT_QUOTES, 'UTF-8');
                        $attribute->setProductValue($val, $product, $channel);
                    }
                }
            } elseif ($attribute->value_per_locale) {
                foreach ($product[ProductAbstractType::LOCALE_VALUES_KEY] ?? [] as $locale => $value) {
                    if (! empty($value[$attribute->code])) {
                        $val = htmlspecialchars($value[$attribute->code], ENT_QUOTES, 'UTF-8');
                        $attribute->setProductValue($val, $product, null, $locale);
                    }
                }
            } else {
                foreach ($product[ProductAbstractType::COMMON_VALUES_KEY] ?? [] as $key => $value) {
                    if (! empty($value) && $key === $attribute->code) {
                        $val = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                        $attribute->setProductValue($val, $product);
                    }
                }
            }
        }

        return $product;
    }

    /**
     * Partisal Updates the simple product.
     */
    public function patchProduct(Product $product, array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($product, $key)) {
                $product->$key = $value;
            }
        }

        if (isset($data['values'])) {
            if (is_string($product->values)) {
                $existingValues = json_decode($product->values, true) ?? [];
            } else {
                // $existingValues = $product->values;
                $existingValues = $product->values ?? [];
            }

            $updatedValues = $this->mergeValues($existingValues, $data['values']);

            $product->values = $updatedValues;
        }

        $product->saveOrFail();

        return $product;
    }

    private function mergeValues(array $existing, array $new)
    {
        foreach ($new as $key => $value) {
            if (is_array($value) && isset($existing[$key]) && is_array($existing[$key])) {

                $existing[$key] = $this->mergeValues($existing[$key], $value);
            } else {
                $existing[$key] = $value;
            }
        }

        return $existing;
    }

    /**
     * Updates the variants of a configurable product.
     *
     * @param  Product  $product  The parent product model.
     * @param  array  $data  The input data containing variant information.
     */
    protected function updateVaraints(Product $product, array $data): void
    {
        $previousVariantIds = $product->variants->pluck('id');

        $productSuperAttributes = $product->super_attributes;

        $productInstance = app(config('product_types.'.$product->type.'.class'));

        if (isset($data['variants'])) {
            foreach ($data['variants'] as $variantId => $variantData) {
                if (Str::contains($variantId, 'variant_')) {
                    $productInstance->createVariant($product, $productSuperAttributes, $variantData);
                } else {
                    if (is_numeric($index = $previousVariantIds->search($variantId))) {
                        $previousVariantIds->forget($index);
                    }

                    $variantData['super_attributes'] = $productSuperAttributes;

                    $productInstance->updateVariant($variantData, $variantId);
                }
            }
        }

        foreach ($previousVariantIds as $variantId) {
            $this->productRepository->delete($variantId);
        }
    }

    /**
     * Retrieves the SKU from the provided product values array.
     *
     * @param  array  $data  The array containing product values.
     * @return string The SKU extracted from the provided product values array.
     */
    protected function getSkuFromValues(array $data): string
    {
        return $data['values']['common']['sku'];
    }

    /**
     * Creates or updates a variant product based on the provided data.
     *
     * @param  array  $data  The input data containing variant and super attribute information.
     * @return \Webkul\Product\Models\Product The updated or created variant product.
     */
    protected function createOrUpdateVariant(array $data): mixed
    {
        $parentProduct = $this->findParentProductOr404($data['parent']);
        $superAttributes = $parentProduct->super_attributes->pluck('code')?->toArray();
        $this->validateVariantConfiguration($parentProduct, $superAttributes, $data);
        $data['super_attributes'] = $superAttributes;
        $parentData['variants'] = $this->setVaraints($parentProduct, $data, $data['sku']);
        $parentData['values'] = $parentProduct->values;
        $parentData['super_attributes'] = $superAttributes;

        $parentProduct = $this->updateProduct($parentData, $parentProduct);

        return $this->findProductOr404($data['sku']);
    }

    /**
     * Validates super attributes for a product based on its type and family.
     *
     * @param  array  $data  The input data containing product type and super attributes.
     * @param  AttributeFamily  $family  The attribute family of the product.
     *
     * @throws ModelNotFoundException If the product family does not have any configurable attributes or if the provided super attributes do not match the family's configuration.
     */
    protected function validateSuperAttributes(array $data, AttributeFamily $family): void
    {
        if (
            ProductType::hasVariants($data['type'])
            && isset($data['super_attributes'])
        ) {
            $configurableAttributes = $family->getConfigurableAttributes()->pluck('code')->toArray();
            if (empty($configurableAttributes)) {
                throw new ModelNotFoundException(trans('admin::app.catalog.products.index.create.not-config-family-error', ['family' => $data['family']]));
            }

            $superAttributes = array_intersect($configurableAttributes, $data['super_attributes']);
            if (empty($superAttributes)) {
                throw new ModelNotFoundException(trans('admin::app.catalog.products.index.create.not-config-super-attributes-error', ['super_attributes' => json_encode($data['super_attributes'], true)]));
            }
        }
    }

    /**
     * Prepares variant data for product update.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $product  The parent product model.
     * @param  array  $data  The input data containing variant and super attribute information.
     * @param  string  $sku  The SKU of the product.
     * @return array The prepared variant data array.
     */
    protected function setVaraints(Product $product, array $data, string $sku): array
    {
        $variantData = [];

        if (isset($data['variant'])) {
            $commonValue = $data['variant']['attributes'];
            $commonValue['sku'] = $sku;
            $variantData['variant_0'] = [
                'sku'    => $sku,
                'values' => [
                    'common' => $commonValue,
                ],
            ];
        }

        $existVariants = $product->variants()->get()?->toArray();
        foreach ($existVariants as $variant) {
            $commonValue = ['sku' => $variant['sku']];
            foreach ($data['super_attributes'] as $key => $attrCode) {
                $commonValue[$attrCode] = $variant['values']['common'][$attrCode];
            }

            $variantData[$variant['id']] = [
                'sku'    => $variant['sku'],
                'values' => [
                    'common' => $commonValue,
                ],
            ];
        }

        return $variantData;
    }

    /**
     * Validates variant configuration for a configurable product.
     *
     * @param  Product  $parentProduct  The parent product model.
     * @param  array  $superAttributes  The super attributes of the parent product.
     * @param  array  $data  The input data containing variant and super attribute information.
     *
     * @throws ModelNotFoundException If a super attribute is not found in the variant data.
     * @throws UnprocessableEntityHttpException If the variant is not unique for the parent product.
     */
    protected function validateVariantConfiguration(Product $parentProduct, array $superAttributes, array $data): void
    {
        foreach ($superAttributes as $attrCode) {
            if (! isset($data['variant']['attributes']) || ! isset($data['variant']['attributes'][$attrCode])) {
                throw new ModelNotFoundException(trans('admin::app.catalog.products.edit.types.configurable.supper-attribute-not-found', ['attribute' => $attrCode]));
            }
            $this->validateVariantOption($attrCode, $data['variant']['attributes']);
            $configurableValues[$attrCode] = $data['variant']['attributes'][$attrCode];
        }

        if (! empty($configurableValues) && $parentProduct) {
            $isUnique = $this->productRepository->isUniqueVariantForProduct(
                productId: $parentProduct->id,
                configAttributes: $configurableValues,
            );

            if (! $isUnique) {
                throw new UnprocessableEntityHttpException(trans('admin::app.catalog.products.edit.types.configurable.variant-given-exists', ['variants' => json_encode($configurableValues)]));
            }
        }
    }

    /**
     * Validates variant option for a configurable product.
     *
     * @param  string  $attrCode  The code of the attribute for which the option is being validated.
     * @param  array  $requestedAttribute  The array containing the requested attribute and its value.
     * @return array The requested attribute array.
     *
     * @throws ModelNotFoundException If the variant attribute option is not found.
     */
    protected function validateVariantOption(string $attrCode, array $requestedAttribute): array
    {
        $unknownOption = $this->attributeRepository->findVariantOption($attrCode, $requestedAttribute[$attrCode])->first()?->id;

        if (! $unknownOption) {
            throw new ModelNotFoundException(trans('admin::app.catalog.products.edit.types.configurable.variant-attribute-option-not-found', ['attributes' => json_encode($requestedAttribute)]));
        }

        return $requestedAttribute;
    }

    /**
     * Finds a product by its SKU and throws a ModelNotFoundException if not found.
     *
     * @param  string  $sku  The SKU of the product to be found.
     * @return Product The found product.
     *
     * @throws ModelNotFoundException If the product is not found.
     */
    protected function findProductOr404(string $sku): Product
    {
        $product = $this->productRepository->findByField('sku', $sku)->first();
        if (! $product) {
            throw new ModelNotFoundException(
                trans('admin::app.catalog.products.product-not-found', ['sku' => $sku])
            );
        }

        return $product;
    }

    /**
     * Finds a parent product by its SKU and throws a ModelNotFoundException if not found.
     *
     * @param  string  $sku  The SKU of the parent product to be found.
     * @return Product The found parent product.
     *
     * @throws ModelNotFoundException If the parent product is not found.
     */
    protected function findParentProductOr404(string $sku): Product
    {
        $product = $this->productRepository->findByField('sku', $sku)->first();
        if (! $product) {
            throw new ModelNotFoundException(
                trans('admin::app.catalog.products.parent-not-found', ['sku' => $sku])
            );
        }

        return $product;
    }

    /**
     * Retrieves an attribute family by its code and throws a ModelNotFoundException if not found.
     *
     * @param  string  $code  The unique code of the attribute family to be retrieved.
     * @return \Webkul\Attribute\Models\AttributeFamily The found attribute family.
     *
     * @throws ModelNotFoundException If the attribute family is not found.
     */
    protected function findFamilyOr404(string $code): AttributeFamily
    {
        $family = $this->attributeFamilyRepository->findByField('code', $code)->first();
        if (! $family) {
            throw new ModelNotFoundException(
                trans('admin::app.catalog.families.not-found', ['family' => $code])
            );
        }

        return $family;
    }
}

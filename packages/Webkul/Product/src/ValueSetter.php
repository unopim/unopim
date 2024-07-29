<?php

namespace Webkul\Product;

use Webkul\Product\Type\AbstractType as ProductAbstractType;

/**
 * This class is responsible for setting and managing product values.
 * It provides methods to set common, category, association, locale-specific, channel-specific, and channel-locale-specific values.
 */
class ValueSetter
{
    /**
     * @var array Holds the product values.
     */
    private $values = [];

    /**
     * Constructor to initialize the product values.
     *
     * @param  array  $initialValues  Initial product values.
     */
    public function __construct($initialValues = [])
    {
        $this->values = $initialValues;
    }

    /**
     * Sets common product values.
     *
     * @param  array  $common  Common product values.
     */
    public function setCommon(array $common)
    {
        $this->values[ProductAbstractType::COMMON_VALUES_KEY] = $common;
    }

    /**
     * Sets category product values.
     *
     * @param  array  $categories  Category product values.
     */
    public function setCategories(array $categories)
    {
        $this->values[ProductAbstractType::CATEGORY_VALUES_KEY] = $categories;
    }

    /**
     * Sets up-sells association product values.
     *
     * @param  array  $data  Up-sells association product values.
     */
    public function setUpSellsAssociation(array $data)
    {
        $this->values[ProductAbstractType::ASSOCIATION_VALUES_KEY][ProductAbstractType::UP_SELLS_ASSOCIATION_KEY] = $data;
    }

    /**
     * Sets cross-sells association product values.
     *
     * @param  array  $data  Cross-sells association product values.
     */
    public function setCrossSellsAssociation(array $data)
    {
        $this->values[ProductAbstractType::ASSOCIATION_VALUES_KEY][ProductAbstractType::CROSS_SELLS_ASSOCIATION_KEY] = $data;
    }

    /**
     * Sets related association product values.
     *
     * @param  array  $data  Related association product values.
     */
    public function setRelatedAssociation(array $data)
    {
        $this->values[ProductAbstractType::ASSOCIATION_VALUES_KEY][ProductAbstractType::RELATED_ASSOCIATION_KEY] = $data;
    }

    /**
     * Sets locale-specific product values.
     *
     * @param  array  $data  Locale-specific product values.
     */
    public function setLocaleSpecific(array $data)
    {
        $this->values[ProductAbstractType::LOCALE_VALUES_KEY] = $data;
    }

    /**
     * Sets channel-specific product values.
     *
     * @param  array  $data  Channel-specific product values.
     */
    public function setChannelSpecific(array $data)
    {
        $this->values[ProductAbstractType::CHANNEL_VALUES_KEY] = $data;
    }

    /**
     * Sets channel-locale-specific product values.
     *
     * @param  array  $data  Channel-locale-specific product values.
     */
    public function setChannelLocaleSpecific(array $data)
    {
        $this->values[ProductAbstractType::CHANNEL_LOCALE_VALUES_KEY] = $data;
    }

    /**
     * Returns the product values.
     *
     * @return array Product values.
     */
    public function getValues()
    {
        return $this->values;
    }
}

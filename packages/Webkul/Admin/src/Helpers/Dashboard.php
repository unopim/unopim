<?php

namespace Webkul\Admin\Helpers;

use Webkul\Admin\Helpers\Reporting\Attribute;
use Webkul\Admin\Helpers\Reporting\AttributeFamily;
use Webkul\Admin\Helpers\Reporting\AttributeGroup;
use Webkul\Admin\Helpers\Reporting\Category;
use Webkul\Admin\Helpers\Reporting\Channel;
use Webkul\Admin\Helpers\Reporting\Currency;
use Webkul\Admin\Helpers\Reporting\Locale;
use Webkul\Admin\Helpers\Reporting\Product;

class Dashboard
{
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(
        protected Product $productReporting,
        protected AttributeFamily $attributeFamily,
        protected Attribute $attribute,
        protected AttributeGroup $attributeGroup,
        protected Category $category,
        protected Locale $locale,
        protected Channel $channel,
        protected Currency $currency
    ) {}

    /**
     * This method calculates and returns the total number of various catalog entities.
     *
     * @return array An associative array containing the total count of each catalog entity.
     */
    public function getTotalCatalogs()
    {
        return [

            'totalCategories' => $this->category->getTotalCategories(),
            'totalProducts'   => $this->productReporting->getTotalProducts(),
        ];
    }

    /**
     * This method calculates and returns the total number of various configuration entities.
     *
     * @return array An associative array containing the total count of each configuration entity.
     */
    public function getTotalConfigurations()
    {
        return [
            'totalCurrencies'        => $this->currency->getTotalActiveCurrencies(),
            'totalChannels'          => $this->channel->getTotalChannels(),
            'totalLocales'           => $this->locale->getTotalActiveLocales(),
            'totalAttributes'        => $this->attribute->getTotalAttributes(),
            'totalAttributeGroups'   => $this->attributeGroup->getTotalAttributeGroups(),
            'totalAttributeFamilies' => $this->attributeFamily->getTotalFamilies(),
        ];
    }
}

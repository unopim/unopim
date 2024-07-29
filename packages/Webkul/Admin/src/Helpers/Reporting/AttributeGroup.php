<?php

namespace Webkul\Admin\Helpers\Reporting;

use Webkul\Attribute\Repositories\AttributeGroupRepository;

class AttributeGroup extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeGroupRepository $attributeGroupRepository,
    ) {}

    /**
     * This method is used to get the total number of attribute groups.
     *
     * @return int The total number of attribute groups.
     */
    public function getTotalAttributeGroups(): int
    {
        return $this->attributeGroupRepository
            ->resetModel()
            ->count();
    }
}

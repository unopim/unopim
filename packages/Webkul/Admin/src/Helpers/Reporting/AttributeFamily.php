<?php

namespace Webkul\Admin\Helpers\Reporting;

use Webkul\Attribute\Repositories\AttributeFamilyRepository;

class AttributeFamily extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
    ) {}

    /**
     * This method returns the total number of attribute families.
     *
     * @return int The total number of attribute families.
     */
    public function getTotalFamilies(): int
    {
        return $this->attributeFamilyRepository
            ->resetModel()
            ->count();
    }
}

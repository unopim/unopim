<?php

namespace Webkul\Admin\Helpers\Reporting;

use Webkul\Attribute\Repositories\AttributeRepository;

class Attribute extends AbstractReporting
{
    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
    ) {}

    /**
     * This method is used to get the total number of attributes.
     *
     * @return int The total number of attributes.
     */
    public function getTotalAttributes(): int
    {
        return $this->attributeRepository
            ->resetModel()
            ->count();
    }
}

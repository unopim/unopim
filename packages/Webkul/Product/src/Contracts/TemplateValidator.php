<?php

namespace Webkul\Product\Contracts;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Product\Templates\Results\ValidationResult;

interface TemplateValidator
{
    /**
     * Validate the template source for the attribute and return the errors and warnings found.
     */
    public function validate(string $source, Attribute $attribute): ValidationResult;
}

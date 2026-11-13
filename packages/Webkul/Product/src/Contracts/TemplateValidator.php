<?php

namespace Webkul\Product\Contracts;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Product\Templates\Results\ValidationResult;

interface TemplateValidator
{
    public function validate(string $source, Attribute $attribute): ValidationResult;
}

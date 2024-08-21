<?php

namespace Webkul\DataTransfer\Validators\Import;

use Webkul\Category\Contracts\CategoryField;
use Webkul\Category\Validator\FieldValidator;

class CategoryRulesExtractor extends FieldValidator
{
    /**
     * Returns the field type rules for the categoryField
     */
    public function getFieldTypeRules(CategoryField $categoryField): array
    {
        return $this->fieldTypeRules($categoryField);
    }

    /**
     * extended abstract function validate from FieldValidator
     */
    public function validate(array $requestData, ?int $id = null) {}
}

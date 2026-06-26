<?php

namespace Webkul\DataTransfer\Enums;

enum ProductFilter: string
{
    case ATTRIBUTE_FAMILIES = 'attribute_families';
    case CATEGORIES = 'categories';
    case COMPLETENESS = 'completeness';
    case TIME_CONDITION = 'time_condition';
    case TIME_VALUE = 'time_value';
    case TIME_DATE = 'time_date';
    case TIME_DATE_END = 'time_date_end';
    case CUSTOM_ATTRIBUTES = 'custom_attributes';
    case UPDATED_AFTER = 'updated_after';
    case UPDATED_BEFORE = 'updated_before';
    case STATUS = 'status';
    case SKU = 'sku';
}

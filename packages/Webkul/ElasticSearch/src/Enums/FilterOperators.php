<?php

namespace Webkul\ElasticSearch\Enums;

enum FilterOperators: string
{
    case PREFIX = 'prefix';
    case SUFFIX = 'suffix';
    case CONTAINS = 'has';
    case EXCLUDES = 'missing';
    case IS_EMPTY = 'blank';
    case IS_NOT_EMPTY = 'not_blank';
    case IN = 'in_list';
    case NOT_IN = 'not_in_list';
    case RANGE = 'within_range';
    case NOT_IN_RANGE = 'outside_range';
    case IS_NULL = 'is_null';
    case IS_NOT_NULL = 'not_null';
    case SIMILAR_TO = 'matches';
    case NOT_SIMILAR_TO = 'no_match';
    case GREATER_THAN = 'gt';
    case GREATER_THAN_OR_EQUAL = 'gte';
    case LESS_THAN = 'lt';
    case LESS_THAN_OR_EQUAL = 'lte';
    case EQUAL = 'eq';
    case NOT_EQUAL = 'neq';

    /**
     * Get the label for each operator.
     */
    public function label(): string
    {
        return match ($this) {
            self::PREFIX                => 'Starts with',
            self::SUFFIX                => 'Ends with',
            self::CONTAINS              => 'Contains',
            self::EXCLUDES              => 'Does not contain',
            self::IS_EMPTY              => 'Is empty',
            self::IS_NOT_EMPTY          => 'Is not empty',
            self::IN                    => 'In list',
            self::NOT_IN                => 'Not in list',
            self::RANGE                 => 'Within range',
            self::NOT_IN_RANGE          => 'Outside range',
            self::IS_NULL               => 'Is null',
            self::IS_NOT_NULL           => 'Is not null',
            self::SIMILAR_TO            => 'Matches',
            self::NOT_SIMILAR_TO        => 'Does not match',
            self::GREATER_THAN          => 'Greater than',
            self::GREATER_THAN_OR_EQUAL => 'Greater than or equal to',
            self::LESS_THAN             => 'Less than',
            self::LESS_THAN_OR_EQUAL    => 'Less than or equal to',
            self::EQUAL                 => 'Equal',
            self::NOT_EQUAL             => 'Not equal',
        };
    }
}
